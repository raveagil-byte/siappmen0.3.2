<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Unit;
use App\Models\Instrument;
use App\Models\InstrumentUnitStatus;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class TransactionService
{
    /**
     * Get available steril instruments from CSSD.
     */
    public function getAvailableSterilInstrumentsForCssd()
    {
        return InstrumentUnitStatus::with('instrument')
            ->where('location', 'cssd')
            ->where('stock_steril', '>', 0)
            ->get();
    }

    /**
     * Get instruments that are in use or dirty within a specific unit.
     */
    public function getInstrumentsInUnit(Unit $unit)
    {
        return InstrumentUnitStatus::with('instrument')
            ->where('unit_id', $unit->id)
            ->where('location', 'unit')
            ->where(function ($query) {
                $query->where('stock_in_use', '>', 0)
                      ->orWhere('stock_kotor', '>', 0');
            })
            ->get();
    }

    /**
     * Create a new steril instrument distribution transaction.
     * Moves stock from CSSD (steril) to a Unit (in_use).
     */
    public function createSterilDistribution(Unit $unit, $user, array $items, $notes = null)
    {
        return DB::transaction(function () use ($unit, $user, $items, $notes) {
            $transaction = Transaction::create([
                'uuid' => (string) Str::uuid(),
                'unit_id' => $unit->id,
                'creator_id' => $user->id,
                'type' => 'distribusi_steril',
                'status' => 'pending',
                'notes' => $notes,
            ]);

            foreach ($items as $item) {
                // 1. Decrement steril stock from CSSD
                $cssdStatus = $this->getOrCreateInstrumentStatus(null, $item['instrument_id'], 'cssd');

                if ($cssdStatus->stock_steril < $item['quantity']) {
                    throw new Exception('Insufficient steril stock in CSSD for instrument ID ' . $item['instrument_id']);
                }
                $cssdStatus->decrement('stock_steril', $item['quantity']);

                // 2. Increment in_use stock in the Unit
                $unitStatus = $this->getOrCreateInstrumentStatus($unit->id, $item['instrument_id'], 'unit');
                $unitStatus->increment('stock_in_use', $item['quantity']);

                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'instrument_id' => $item['instrument_id'],
                    'quantity' => $item['quantity'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            ActivityLog::log('create_transaction', $user, 'Created steril distribution transaction', null, $transaction->id, $user->role);

            return $transaction;
        });
    }

    /**
     * Create a new dirty instrument pickup transaction.
     * Moves stock from a Unit (in_use) to the same Unit (kotor).
     */
    public function createKotorPickup(Unit $unit, $user, array $items, $notes = null)
    {
        return DB::transaction(function () use ($unit, $user, $items, $notes) {
            $transaction = Transaction::create([
                'uuid' => (string) Str::uuid(),
                'unit_id' => $unit->id,
                'creator_id' => $user->id,
                'type' => 'pengambilan_kotor',
                'status' => 'pending',
                'notes' => $notes,
            ]);

            foreach ($items as $item) {
                // 1. Check if there is enough stock_in_use in the unit
                $unitStatus = $this->getOrCreateInstrumentStatus($unit->id, $item['instrument_id'], 'unit');

                if ($unitStatus->stock_in_use < $item['quantity']) {
                    throw new Exception('Insufficient in-use stock in unit for instrument ID ' . $item['instrument_id']);
                }

                // 2. Move stock from in_use to kotor within the same unit
                $unitStatus->decrement('stock_in_use', $item['quantity']);
                $unitStatus->increment('stock_kotor', $item['quantity']);

                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'instrument_id' => $item['instrument_id'],
                    'quantity' => $item['quantity'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            ActivityLog::log('create_transaction', $user, 'Created dirty pickup transaction', null, $transaction->id, $user->role);

            return $transaction;
        });
    }

    /**
     * Create a transaction to return dirty instruments from a Unit to CSSD.
     * Moves stock from Unit (kotor) to CSSD (kotor).
     */
    public function createCssdReturn(Unit $unit, $user, array $items, $notes = null)
    {
        return DB::transaction(function () use ($unit, $user, $items, $notes) {
            $transaction = Transaction::create([
                'uuid' => (string) Str::uuid(),
                'unit_id' => $unit->id,
                'creator_id' => $user->id,
                'type' => 'pengembalian_cssd',
                'status' => 'pending',
                'notes' => $notes,
            ]);

            foreach ($items as $item) {
                // 1. Decrement kotor stock from the Unit
                $unitStatus = $this->getOrCreateInstrumentStatus($unit->id, $item['instrument_id'], 'unit');

                if ($unitStatus->stock_kotor < $item['quantity']) {
                    throw new Exception('Insufficient dirty stock in unit for instrument ID ' . $item['instrument_id']);
                }
                $unitStatus->decrement('stock_kotor', $item['quantity']);

                // 2. Increment kotor stock in CSSD
                $cssdStatus = $this->getOrCreateInstrumentStatus(null, $item['instrument_id'], 'cssd');
                $cssdStatus->increment('stock_kotor', $item['quantity']);

                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'instrument_id' => $item['instrument_id'],
                    'quantity' => $item['quantity'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            ActivityLog::log('create_transaction', $user, 'Created CSSD return transaction', null, $transaction->id, $user->role);

            return $transaction;
        });
    }

    /**
     * Validate a pending transaction.
     */
    public function validateTransaction(Transaction $transaction, $user)
    {
        if ($transaction->status !== 'pending') {
            throw new Exception('Transaction has already been processed.');
        }

        $transaction->validator_id = $user->id;
        $transaction->status = 'validated';
        $transaction->save();

        // Stock movements are finalized at creation. Validation is for workflow approval.
        ActivityLog::log('validate_transaction', $user, 'Validated transaction', null, $transaction->id, $user->role);

        return $transaction;
    }

    /**
     * Cancel a transaction and revert stock changes.
     */
    public function cancelTransaction(Transaction $transaction, $user, string $reason)
    {
        if ($transaction->status === 'cancelled') {
            throw new Exception('Transaction already cancelled.');
        }

        return DB::transaction(function () use ($transaction, $user, $reason) {
            // Only pending transactions can have their stock reverted.
            // A validated transaction is considered final and should not be reverted automatically.
            if ($transaction->status === 'pending') {
                $this->revertStockChanges($transaction);
            }

            $transaction->status = 'cancelled';
            $transaction->cancel_reason = $reason;
            $transaction->save();

            ActivityLog::log('cancel_transaction', $user, 'Cancelled transaction: ' . $reason, null, $transaction->id, $user->role);

            return $transaction;
        });
    }

    /**
     * Helper to revert stock changes for a given transaction.
     */
    private function revertStockChanges(Transaction $transaction)
    {
        foreach ($transaction->items as $item) {
            switch ($transaction->type) {
                case 'distribusi_steril':
                    // Revert: Add stock back to CSSD steril, remove from Unit in_use
                    $cssdStatus = $this->getOrCreateInstrumentStatus(null, $item->instrument_id, 'cssd');
                    $cssdStatus->increment('stock_steril', $item->quantity);

                    $unitStatus = $this->getOrCreateInstrumentStatus($transaction->unit_id, $item->instrument_id, 'unit');
                    $unitStatus->decrement('stock_in_use', $item->quantity);
                    break;

                case 'pengambilan_kotor':
                    // Revert: Move stock from Unit kotor back to Unit in_use
                    $unitStatus = $this->getOrCreateInstrumentStatus($transaction->unit_id, $item->instrument_id, 'unit');
                    $unitStatus->decrement('stock_kotor', $item->quantity);
                    $unitStatus->increment('stock_in_use', $item->quantity);
                    break;

                case 'pengembalian_cssd':
                    // Revert: Move stock from CSSD kotor back to Unit kotor
                    $unitStatus = $this->getOrCreateInstrumentStatus($transaction->unit_id, $item->instrument_id, 'unit');
                    $unitStatus->increment('stock_kotor', $item->quantity);

                    $cssdStatus = $this->getOrCreateInstrumentStatus(null, $item->instrument_id, 'cssd');
                    $cssdStatus->decrement('stock_kotor', $item->quantity);
                    break;
            }
        }
    }

    /**
     * Get or create an instrument status record.
     */
    private function getOrCreateInstrumentStatus($unitId, $instrumentId, $location)
    {
        // For CSSD, unit_id is always null.
        $actualUnitId = $location === 'cssd' ? null : $unitId;

        return InstrumentUnitStatus::firstOrCreate(
            [
                'instrument_id' => $instrumentId,
                'unit_id' => $actualUnitId,
                'location' => $location,
            ],
            [
                'stock_steril' => 0,
                'stock_kotor' => 0,
                'stock_in_use' => 0,
            ]
        );
    }
}
