<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with(['user', 'transaction.unit', 'transaction.items.instrument']);

        // Add filtering logic here if needed

        $activityLogs = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $activityLogs,
        ]);
    }

    public function show(ActivityLog $activityLog)
    {
        $activityLog->load(['user', 'transaction.unit', 'transaction.items.instrument']);

        return response()->json([
            'success' => true,
            'data' => $activityLog,
        ]);
    }
}
