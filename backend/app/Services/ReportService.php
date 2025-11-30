<?php

namespace App\Services;

use App\Exports\TransactionsExport;
use App\Exports\StockExport;
use App\Exports\ActivityLogsExport;
use Maatwebsite\Excel\Facades\Excel;

class ReportService
{
    public function export(string $type)
    {
        switch ($type) {
            case 'transactions':
                return Excel::download(new TransactionsExport, 'transactions.xlsx');
            case 'stock':
                return Excel::download(new StockExport, 'stock.xlsx');
            case 'activity':
                return Excel::download(new ActivityLogsExport, 'activity_logs.xlsx');
            default:
                abort(400, 'Invalid export type');
        }
    }
}
