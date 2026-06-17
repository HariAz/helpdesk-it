<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityLog;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->orderByDesc('created_at');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('action', 'like', "%{$request->search}%")
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$request->search}%"));
            });
        }
        if ($request->action) $query->where('action', $request->action);
        if ($request->user_id) $query->where('user_id', $request->user_id);
        if ($request->date_from) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->date_to) $query->whereDate('created_at', '<=', $request->date_to);

        $logs = $query->paginate(50)->withQueryString();
        $actions = ActivityLog::distinct()->pluck('action');

        return view('activity-logs.index', compact('logs', 'actions'));
    }

    public function export(Request $request)
    {
        $logs = ActivityLog::with('user')->orderByDesc('created_at')->get();

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, ['Waktu', 'User', 'Aksi', 'IP Address', 'User Agent']);
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user?->name ?? 'System',
                    $log->action,
                    $log->ip_address,
                    $log->user_agent,
                ]);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, 'activity-logs-' . now()->format('Ymd') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
