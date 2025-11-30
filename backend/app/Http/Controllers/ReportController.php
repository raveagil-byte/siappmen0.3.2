<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected $reportService;
    
    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Export report data to Excel
     * URL example: /api/report/export-excel?type=transactions
     */
    public function exportExcel(Request $request)
    {
        $request->validate([
            'type' => 'required|in:transactions,stock,activity',
        ]);

        $type = $request->type;

        return $this->reportService->export($type);
    }
}
