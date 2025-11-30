<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\InstrumentUnitStatus;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdvancedReportController extends Controller
{
    /**
     * Get comprehensive dashboard analytics
     */
    public function analytics(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth());

        // Transaction volume by type
        $transactionVolume = Transaction::whereBetween('created_at', [$startDate, $endDate])
            ->select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->get();

        // Stock levels summary
        $stockSummary = InstrumentUnitStatus::select(
            DB::raw('SUM(stock_steril) as total_steril'),
            DB::raw('SUM(stock_kotor) as total_kotor'),
            DB::raw('SUM(stock_in_use) as total_in_use'),
            DB::raw('SUM(stock_cssd) as total_cssd')
        )->first();

        // Unit performance metrics
        $unitPerformance = DB::table('transactions')
            ->join('units', 'transactions.unit_id', '=', 'units.id')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->select(
                'units.name as unit_name',
                DB::raw('COUNT(transactions.id) as transaction_count'),
                DB::raw('AVG(TIMESTAMPDIFF(MINUTE, transactions.created_at, transactions.updated_at)) as avg_processing_time')
            )
            ->groupBy('units.id', 'units.name')
            ->orderBy('transaction_count', 'desc')
            ->get();

        // Peak usage hours
        $peakHours = DB::table('transactions')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->orderBy('transaction_count', 'desc')
            ->get();

        // Instrument utilization rates
        $instrumentUtilization = DB::table('instrument_unit_status')
            ->join('instruments', 'instrument_unit_status.instrument_id', '=', 'instruments.id')
            ->select(
                'instruments.name',
                DB::raw('AVG(stock_steril) as avg_steril_stock'),
                DB::raw('AVG(stock_kotor) as avg_kotor_stock'),
                DB::raw('AVG(stock_in_use) as avg_in_use'),
                DB::raw('SUM(stock_steril + stock_kotor + stock_in_use) as total_stock')
            )
            ->groupBy('instruments.id', 'instruments.name')
            ->orderBy('total_stock', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'transaction_volume' => $transactionVolume,
            'stock_summary' => $stockSummary,
            'unit_performance' => $unitPerformance,
            'peak_hours' => $peakHours,
            'instrument_utilization' => $instrumentUtilization,
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate
            ]
        ]);
    }

    /**
     * Get predictive analytics for stock requirements
     */
    public function predictiveAnalytics(Request $request)
    {
        $days = $request->get('days', 30);

        // Calculate average daily consumption per instrument per unit
        $consumptionTrends = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.type', 'steril')
            ->where('transactions.created_at', '>=', Carbon::now()->subDays($days))
            ->select(
                'transaction_items.instrument_id',
                'transactions.unit_id',
                DB::raw('SUM(transaction_items.quantity) / ' . $days . ' as daily_consumption'),
                DB::raw('COUNT(DISTINCT DATE(transactions.created_at)) as active_days')
            )
            ->groupBy('transaction_items.instrument_id', 'transactions.unit_id')
            ->get();

        // Predict stock requirements for next week
        $predictions = [];
        foreach ($consumptionTrends as $trend) {
            $currentStock = InstrumentUnitStatus::where('unit_id', $trend->unit_id)
                ->where('instrument_id', $trend->instrument_id)
                ->first();

            if ($currentStock) {
                $predictedNeed = $trend->daily_consumption * 7; // 7 days
                $recommendedStock = $predictedNeed * 1.2; // 20% buffer

                $predictions[] = [
                    'unit_id' => $trend->unit_id,
                    'instrument_id' => $trend->instrument_id,
                    'current_stock' => $currentStock->stock_steril,
                    'daily_consumption' => round($trend->daily_consumption, 2),
                    'predicted_weekly_need' => round($predictedNeed, 2),
                    'recommended_stock' => round($recommendedStock, 2),
                    'stock_status' => $currentStock->stock_steril >= $recommendedStock ? 'sufficient' : 'low'
                ];
            }
        }

        return response()->json([
            'predictions' => $predictions,
            'analysis_period_days' => $days,
            'generated_at' => Carbon::now()
        ]);
    }

    /**
     * Export advanced reports in various formats
     */
    public function export(Request $request)
    {
        $type = $request->get('type', 'analytics');
        $format = $request->get('format', 'json');

        switch ($type) {
            case 'analytics':
                $data = $this->analytics($request)->getData();
                break;
            case 'predictive':
                $data = $this->predictiveAnalytics($request)->getData();
                break;
            default:
                return response()->json(['error' => 'Invalid report type'], 400);
        }

        if ($format === 'csv') {
            return $this->exportToCsv($data, $type);
        }

        return response()->json($data);
    }

    /**
     * Export data to CSV format
     */
    private function exportToCsv($data, $type)
    {
        $filename = $type . '_report_' . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        // For simplicity, return JSON for now
        // In production, you'd implement proper CSV generation
        return response()->json($data, 200, $headers);
    }

    /**
     * Get system health metrics
     */
    public function systemHealth()
    {
        // Database connection status
        try {
            DB::connection()->getPdo();
            $dbStatus = 'healthy';
        } catch (\Exception $e) {
            $dbStatus = 'unhealthy';
        }

        // Queue status (if using queues)
        $queueStatus = 'not_configured'; // Placeholder

        // Cache status
        try {
            \Cache::store()->getStore()->connection()->ping();
            $cacheStatus = 'healthy';
        } catch (\Exception $e) {
            $cacheStatus = 'unhealthy';
        }

        // Recent errors from logs
        $recentErrors = ActivityLog::where('action', 'error')
            ->orWhere('description', 'like', '%error%')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'database' => $dbStatus,
            'cache' => $cacheStatus,
            'queue' => $queueStatus,
            'recent_errors' => $recentErrors,
            'timestamp' => Carbon::now()
        ]);
    }
}
