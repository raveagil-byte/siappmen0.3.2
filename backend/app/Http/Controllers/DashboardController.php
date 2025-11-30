<?php

namespace App\Http\Controllers;

use App\Models\Instrument;
use App\Models\InstrumentUnitStatus;
use App\Models\Transaction;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function stats(Request $request)
    {
        // Total counts
        $totalUnits = Unit::where('is_active', true)->count();
        $totalInstruments = Instrument::where('is_active', true)->count();
        $totalTransactions = Transaction::count();
        $pendingTransactions = Transaction::where('status', 'pending')->count();

        // Stock summary
        $cssdStock = InstrumentUnitStatus::whereNull('unit_id')
            ->select(
                DB::raw('SUM(stock_steril) as total_steril'),
                DB::raw('SUM(stock_kotor) as total_kotor')
            )
            ->first();

        $unitStock = InstrumentUnitStatus::whereNotNull('unit_id')
            ->select(
                DB::raw('SUM(stock_steril) as total_steril'),
                DB::raw('SUM(stock_kotor) as total_kotor'),
                DB::raw('SUM(stock_in_use) as total_in_use')
            )
            ->first();

        // Recent transactions
        $recentTransactions = Transaction::with(['unit', 'creator'])
            ->latest()
            ->limit(10)
            ->get();

        // Transactions by type (last 30 days)
        $transactionsByType = Transaction::where('created_at', '>=', now()->subDays(30))
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get();

        // Transactions by status
        $transactionsByStatus = Transaction::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        // Daily transactions (last 7 days)
        $dailyTransactions = Transaction::where('created_at', '>=', now()->subDays(7))
            ->select(
                DB::raw('DATE(created_at) as date'),
                'type',
                DB::raw('count(*) as count')
            )
            ->groupBy('date', 'type')
            ->orderBy('date')
            ->get();

        // Stock by unit
        $stockByUnit = Unit::with(['instrumentStatuses' => function ($query) {
            $query->select(
                'unit_id',
                DB::raw('SUM(stock_steril) as total_steril'),
                DB::raw('SUM(stock_kotor) as total_kotor'),
                DB::raw('SUM(stock_in_use) as total_in_use')
            )->groupBy('unit_id');
        }])->get();

        // Low stock instruments (CSSD steril < 10)
        $lowStockInstruments = InstrumentUnitStatus::whereNull('unit_id')
            ->where('stock_steril', '<', 10)
            ->with('instrument')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_units' => $totalUnits,
                    'total_instruments' => $totalInstruments,
                    'total_transactions' => $totalTransactions,
                    'pending_transactions' => $pendingTransactions,
                ],
                'stock' => [
                    'cssd' => [
                        'steril' => $cssdStock->total_steril ?? 0,
                        'kotor' => $cssdStock->total_kotor ?? 0,
                    ],
                    'units' => [
                        'steril' => $unitStock->total_steril ?? 0,
                        'kotor' => $unitStock->total_kotor ?? 0,
                        'in_use' => $unitStock->total_in_use ?? 0,
                    ],
                ],
                'recent_transactions' => $recentTransactions,
                'transactions_by_type' => $transactionsByType,
                'transactions_by_status' => $transactionsByStatus,
                'daily_transactions' => $dailyTransactions,
                'stock_by_unit' => $stockByUnit,
                'low_stock_instruments' => $lowStockInstruments,
            ],
        ]);
    }
}