<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Unit;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Display a listing of the transactions.
     */
    public function index(Request $request)
    {
        $transactions = Transaction::with(['unit', 'creator', 'validator', 'items.instrument'])
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json(['success' => true, 'data' => $transactions]);
    }

    /**
     * Display the specified transaction.
     */
    public function show(Transaction $transaction)
    {
        $transaction->load(['unit', 'creator', 'validator', 'items.instrument', 'photos', 'activityLogs']);
        return response()->json(['success' => true, 'data' => $transaction]);
    }

    /**
     * Scan a unit's QR code to get its details before a transaction.
     */
    public function scanUnit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'qr_content' => 'required|string|starts_with:UNIT:',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $uuid = str_replace('UNIT:', '', $request->qr_content);
        $unit = Unit::where('uuid', $uuid)->first();

        if (!$unit) {
            return response()->json(['success' => false, 'message' => 'Unit not found.'], 404);
        }

        return response()->json(['success' => true, 'data' => ['unit' => $unit]]);
    }

    /**
     * Create a new steril instrument distribution transaction.
     */
    public function createSterilDistribution(Request $request)
    {
        $validator = $this->validateTransactionItems($request);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $unit = Unit::findOrFail($request->unit_id);
        $transaction = $this->transactionService->createSterilDistribution($unit, $request->user(), $request->items, $request->notes);

        return response()->json(['success' => true, 'message' => 'Steril distribution transaction created successfully.', 'data' => $transaction], 201);
    }

    /**
     * Create a new dirty instrument pickup transaction.
     */
    public function createKotorPickup(Request $request)
    {
        $validator = $this->validateTransactionItems($request);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $unit = Unit::findOrFail($request->unit_id);
        $transaction = $this->transactionService->createKotorPickup($unit, $request->user(), $request->items, $request->notes);

        return response()->json(['success' => true, 'message' => 'Dirty pickup transaction created successfully.', 'data' => $transaction], 201);
    }

    /**
     * Create a new transaction to return dirty instruments to CSSD.
     */
    public function createCssdReturn(Request $request)
    {
        $validator = $this->validateTransactionItems($request);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $unit = Unit::findOrFail($request->unit_id);
        $transaction = $this->transactionService->createCssdReturn($unit, $request->user(), $request->items, $request->notes);

        return response()->json(['success' => true, 'message' => 'CSSD return transaction created successfully.', 'data' => $transaction], 201);
    }

    /**
     * Validate a pending transaction.
     */
    public function validateTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|exists:transactions,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $transaction = Transaction::findOrFail($request->transaction_id);
        $validatedTransaction = $this->transactionService->validateTransaction($transaction, $request->user());

        return response()->json(['success' => true, 'message' => 'Transaction validated successfully.', 'data' => $validatedTransaction]);
    }

    /**
     * Cancel a transaction.
     */
    public function cancel(Request $request, Transaction $transaction)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $cancelledTransaction = $this->transactionService->cancelTransaction($transaction, $request->user(), $request->reason);

        return response()->json(['success' => true, 'message' => 'Transaction cancelled successfully.', 'data' => $cancelledTransaction]);
    }

    /**
     * Helper to validate common transaction items.
     */
    private function validateTransactionItems(Request $request)
    {
        return Validator::make($request->all(), [
            'unit_id' => 'required|exists:units,id',
            'items' => 'required|array|min:1',
            'items.*.instrument_id' => 'required|exists:instruments,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
    }
}
