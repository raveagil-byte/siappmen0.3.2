<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Unit;
use App\Models\InstrumentUnitStatus;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Exception;

class TransactionService
{
    /**
     * Get available steril instruments for a unit
     */
    public function getAvailableSterilInstruments(Unit $unit)
    {
        $statuses = InstrumentUnitStatus::with('instrument')
            ->where('unit_id', $unit->id)
            ->where('stock_steril', '>', 0)
            ->get();

        return $statuses->map(function ($status) {
            return [
                'instrument' => $status->instrument,
                'stock_steril' => $status->stock_steril,
            ];
        });
    }

    /**
     * Get instruments currently "kotor" (dirty) in a unit
     */
    public function getKotorInstrumentsInUnit(Unit $unit)
    {
        $statuses = InstrumentUnitStatus::with('instrument')
            ->where('unit_id', $unit->id)
            ->where('stock_kotor', '>', 0)
            ->get();

        return $statuses->map(function ($status) {
            return [
                'instrument' => $status->instrument,
                'stock_kotor' => $status->stock_kotor,
            ];
        });
    }

    /**
     * Create steril transaction
     */
    public function createSterilTransaction(Unit $unit, $user, array $items, $notes = null)
    {
        return DB::transaction(function () use ($unit, $user, $items, $notes) {
            $transaction = new Transaction();
            $transaction->uuid = (string) \Str::uuid();
            $transaction->unit_id = $unit->id;
            $transaction->creator_id = $user->id;
            $transaction->type = 'steril';
            $transaction->status = 'pending';
            $transaction->notes = $notes;
            $transaction->save();

            foreach ($items as $item) {
                $instrumentStatus = InstrumentUnitStatus::where('unit_id', $unit->id)
                    ->where('instrument_id', $item['instrument_id'])
                    ->first();

                if (!$instrumentStatus || $instrumentStatus->stock_steril < $item['quantity']) {
                    throw new Exception('Insufficient steril stock for instrument ID ' . $item['instrument_id']);
                }

                // Decrement steril stock
                $instrumentStatus->stock_steril -= $item['quantity'];
                $instrumentStatus->stock_in_use += $item['quantity'];
                $instrumentStatus->save();

                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'instrument_id' => $item['instrument_id'],
                    'quantity' => $item['quantity'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            ActivityLog::log('create_transaction', $user, 'Created steril transaction', null, $transaction->id, $user->role);

            return $transaction;
        });
    }

    /**
     * Create kotor transaction (dirty pickup)
     */
    public function createKotorTransaction(Unit $unit, $user, array $items, $notes = null)
    {
        return DB::transaction(function () use ($unit, $user, $items, $notes) {
            $transaction = new Transaction();
            $transaction->uuid = (string) \Str::uuid();
            $transaction->unit_id = $unit->id;
            $transaction->creator_id = $user->id;
            $transaction->type = 'kotor';
            $transaction->status = 'pending';
            $transaction->notes = $notes;
            $transaction->save();

            foreach ($items as $item) {
                $instrumentStatus = InstrumentUnitStatus::where('unit_id', $unit->id)
                    ->where('instrument_id', $item['instrument_id'])
                    ->first();

                if (!$instrumentStatus || $instrumentStatus->stock_kotor < $item['quantity']) {
                    throw new Exception('Insufficient dirty stock for instrument ID ' . $item['instrument_id']);
                }

                // Decrement kotor stock in unit
                $instrumentStatus->stock_kotor -= $item['quantity'];
                $instrumentStatus->status = 'cssd'; // Instruments now in CSSD
                $instrumentStatus->save();

                // Increment CSSD kotor stock
                $cssdStatus = InstrumentUnitStatus::where('unit_id', null)
                    ->where('instrument_id', $item['instrument_id'])
                    ->first();
                if ($cssdStatus) {
                    $cssdStatus->stock_kotor += $item['quantity'];
                    $cssdStatus->status = 'cssd';
                    $cssdStatus->save();
                }

                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'instrument_id' => $item['instrument_id'],
                    'quantity' => $item['quantity'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            ActivityLog::log('create_transaction', $user, 'Created kotor transaction', null, $transaction->id, $user->role);

            return $transaction;
        });
    }

    /**
     * Validate transaction
     */
    public function validateTransaction(Transaction $transaction, $user)
    {
        return DB::transaction(function () use ($transaction, $user) {
            if ($transaction->status !== 'pending') {
                throw new Exception('Transaction already validated or cancelled.');
            }

            $transaction->validator_id = $user->id;
            $transaction->status = 'validated';
            $transaction->save();

            // Update stock status depending on transaction type
            foreach ($transaction->items as $item) {
                $instrumentStatus = InstrumentUnitStatus::where('unit_id', $transaction->unit_id)
                    ->where('instrument_id', $item->instrument_id)
                    ->first();

                if (!$instrumentStatus) continue;

                if ($transaction->type === 'steril') {
                    // Validation of steril transaction: instruments are now in use by unit
                    // Decrement in_use (which was incremented during creation), no change to steril stock
                    $instrumentStatus->stock_in_use -= $item->quantity;
                    // Ensure stock doesn't go negative
                    $instrumentStatus->stock_in_use = max(0, $instrumentStatus->stock_in_use);
                } else if ($transaction->type === 'kotor') {
                    // Validation of kotor transaction: instruments returned to CSSD
                    // No additional stock changes needed here - kotor stock was already decremented during creation
                    // and CSSD stock was already incremented during creation
                }

                $instrumentStatus->save();
            }

            ActivityLog::log('validate_transaction', $user, 'Validated transaction', null, $transaction->id, $user->role);

            return $transaction;
        });
    }

    /**
     * Cancel transaction
     */
    public function cancelTransaction(Transaction $transaction, $user, string $reason)
    {
        return DB::transaction(function () use ($transaction, $user, $reason) {
            if ($transaction->status === 'cancelled') {
                throw new Exception('Transaction already cancelled.');
            }

            // Revert stock changes based on transaction type and status
            if ($transaction->status === 'pending') {
                // Revert stock changes made during creation
                foreach ($transaction->items as $item) {
                    $instrumentStatus = InstrumentUnitStatus::where('unit_id', $transaction->unit_id)
                        ->where('instrument_id', $item->instrument_id)
                        ->first();

                    if (!$instrumentStatus) continue;

                    if ($transaction->type === 'steril') {
                        // Revert: decrement in_use, increment steril
                        $instrumentStatus->stock_in_use -= $item->quantity;
                        $instrumentStatus->stock_steril += $item->quantity;
                    } else if ($transaction->type === 'kotor') {
                        // Revert: increment kotor stock (was decremented during creation)
                        $instrumentStatus->stock_kotor += $item->quantity;
                        // Also revert CSSD stock if it was incremented
                        $cssdStatus = InstrumentUnitStatus::where('unit_id', null)
                            ->where('instrument_id', $item->instrument_id)
                            ->first();
                        if ($cssdStatus) {
                            $cssdStatus->stock_kotor -= $item->quantity;
                            $cssdStatus->save();
                        }
                    }


                    $instrumentStatus->save();
                }
            } else if ($transaction->status === 'validated') {
                // Revert stock changes made during validation
                foreach ($transaction->items as $item) {
                    $instrumentStatus = InstrumentUnitStatus::where('unit_id', $transaction->unit_id)
                        ->where('instrument_id', $item->instrument_id)
                        ->first();

                    if (!$instrumentStatus) continue;

                    if ($transaction->type === 'steril') {
                        // Revert validation: instruments go back to "in use" state
                        // Increment in_use (undo the decrement done during validation)
                        $instrumentStatus->stock_in_use += $item->quantity;
                        // Ensure stock doesn't go negative
                        $instrumentStatus->stock_in_use = max(0, $instrumentStatus->stock_in_use);
                    } else if ($transaction->type === 'kotor') {
                        // Kotor validation doesn't change stock, only creation does
                        // But we need to revert the creation changes since validation confirmed the pickup
                        // Increment kotor stock back (it was decremented during creation)
                        $instrumentStatus->stock_kotor += $item->quantity;
                        // Decrement CSSD stock (it was incremented during creation)
                        $cssdStatus = InstrumentUnitStatus::where('unit_id', null)
                            ->where('instrument_id', $item->instrument_id)
                            ->first();
                        if ($cssdStatus) {
                            $cssdStatus->stock_kotor -= $item->quantity;
                            $cssdStatus->stock_kotor = max(0, $cssdStatus->stock_kotor);
                            $cssdStatus->save();
                        }
                    }

                    $instrumentStatus->save();
                }
            }

            $transaction->status = 'cancelled';
            $transaction->cancel_reason = $reason;
            $transaction->save();

            ActivityLog::log('cancel_transaction', $user, 'Cancelled transaction: ' . $reason, null, $transaction->id, $user->role);

            return $transaction;
        });
    }
}
