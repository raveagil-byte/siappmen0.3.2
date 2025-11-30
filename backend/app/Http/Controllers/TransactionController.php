<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Unit;
use App\Services\QRService;
use App\Services\TransactionService;
use App\Http\Requests\CreateTransactionRequest;
use App\Http\Requests\CancelTransactionRequest;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    protected $transactionService;
    protected $qrService;

    public function __construct(TransactionService $transactionService, QRService $qrService)
    {
        $this->transactionService = $transactionService;
        $this->qrService = $qrService;
    }

    /**
     * Scan QR Unit and get available instruments
     */
    public function scanUnit(Request $request)
    {
        $validated = $request->validate([
            'qr_content' => 'required|string',
            'transaction_type' => 'required|in:steril,kotor',
        ]);

        try {
            // Parse QR code
            $parsed = $this->qrService->parseQRCode($validated['qr_content']);

            if ($parsed['type'] !== 'UNIT') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid QR code. Expected Unit QR code.',
                ], 422);
            }

            // Find unit
            $unit = Unit::where('uuid', $parsed['uuid'])->first();

            if (!$unit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unit not found',
                ], 404);
            }

            // Get available instruments based on transaction type
            if ($validated['transaction_type'] === 'steril') {
                $instruments = $this->transactionService->getAvailableSterilInstruments($unit);
            } else {
                $instruments = $this->transactionService->getKotorInstrumentsInUnit($unit);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'unit' => $unit,
                    'instruments' => $instruments,
                    'transaction_type' => $validated['transaction_type'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Create steril distribution transaction
     */
    public function createSteril(CreateTransactionRequest $request)
    {
        $validated = $request->validated();

        try {
            $unit = Unit::findOrFail($validated['unit_id']);
            $transaction = $this->transactionService->createSterilTransaction(
                $unit,
                $request->user(),
                $validated['items'],
                $validated['notes'] ?? null
            );

            // Generate QR code base64 for immediate display
            $qrContent = $this->qrService->generateTransactionQRContent($transaction->uuid);
            $qrBase64 = $this->qrService->generateQRCodeBase64($qrContent);

            return response()->json([
                'success' => true,
                'message' => 'Steril distribution transaction created successfully',
                'data' => [
                    'transaction' => $transaction,
                    'qr_content' => $qrContent,
                    'qr_base64' => $qrBase64,
                    'qr_url' => $transaction->qr_code_url,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Create kotor pickup transaction
     */
    public function createKotor(CreateTransactionRequest $request)
    {
        $validated = $request->validated();

        try {
            $unit = Unit::findOrFail($validated['unit_id']);
            $transaction = $this->transactionService->createKotorTransaction(
                $unit,
                $request->user(),
                $validated['items'],
                $validated['notes'] ?? null
            );

            // Generate QR code base64 for immediate display
            $qrContent = $this->qrService->generateTransactionQRContent($transaction->uuid);
            $qrBase64 = $this->qrService->generateQRCodeBase64($qrContent);

            return response()->json([
                'success' => true,
                'message' => 'Kotor pickup transaction created successfully',
                'data' => [
                    'transaction' => $transaction,
                    'qr_content' => $qrContent,
                    'qr_base64' => $qrBase64,
                    'qr_url' => $transaction->qr_code_url,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Validate transaction by scanning TRANS QR
     */
    public function validateTransaction(Request $request)
    {
        $validated = $request->validate([
            'qr_content' => 'required|string',
        ]);

        try {
            // Parse QR code
            $parsed = $this->qrService->parseQRCode($validated['qr_content']);

            if ($parsed['type'] !== 'TRANS') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid QR code. Expected Transaction QR code.',
                ], 422);
            }

            // Find transaction
            $transaction = Transaction::where('uuid', $parsed['uuid'])->first();

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found',
                ], 404);
            }

            // Validate transaction
            $transaction = $this->transactionService->validateTransaction($transaction, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Transaction validated successfully',
                'data' => $transaction,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get list of transactions
     */
    public function index(Request $request)
    {
        $query = Transaction::with(['unit', 'creator', 'validator', 'items.instrument']);

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by unit
        if ($request->has('unit_id')) {
            $query->where('unit_id', $request->unit_id);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Sort
        $query->orderBy('created_at', 'desc');

        $transactions = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Get transaction details
     */
    public function show(Transaction $transaction)
    {
        $transaction->load([
            'unit',
            'creator',
            'validator',
            'items.instrument',
            'activityLogs.user',
        ]);

        // Generate QR base64
        $qrContent = $this->qrService->generateTransactionQRContent($transaction->uuid);
        $qrBase64 = $this->qrService->generateQRCodeBase64($qrContent);

        return response()->json([
            'success' => true,
            'data' => [
                'transaction' => $transaction,
                'qr_content' => $qrContent,
                'qr_base64' => $qrBase64,
            ],
        ]);
    }

    /**
     * Cancel transaction
     */
    public function cancel(Request $request, Transaction $transaction)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $transaction = $this->transactionService->cancelTransaction(
                $transaction,
                $request->user(),
                $validated['reason']
            );

            return response()->json([
                'success' => true,
                'message' => 'Transaction cancelled successfully',
                'data' => $transaction,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
