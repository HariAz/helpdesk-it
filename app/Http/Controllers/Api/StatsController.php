<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->isSupervisor()) {
            return response()->json(['message' => 'Hanya supervisor yang dapat mengakses stats.'], 403);
        }

        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : now()->startOfMonth();
        $dateTo   = $request->date_to   ? Carbon::parse($request->date_to)->endOfDay()     : now()->endOfDay();

        $query = Ticket::whereBetween('created_at', [$dateFrom, $dateTo]);

        $stats = [
            'period' => [
                'from' => $dateFrom->toDateString(),
                'to'   => $dateTo->toDateString(),
            ],
            'tickets' => [
                'total'     => $query->count(),
                'resolved'  => (clone $query)->whereIn('status', ['resolved', 'closed'])->count(),
                'active'    => Ticket::whereNotIn('status', ['closed', 'cancelled'])->count(),
                'escalated' => (clone $query)->where('is_escalated', true)->count(),
                'unassigned' => Ticket::whereNull('assigned_to')->whereNotIn('status', ['closed', 'cancelled'])->count(),
            ],
            'by_priority' => (clone $query)->selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')->pluck('count', 'priority'),
            'by_status'   => (clone $query)->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')->pluck('count', 'status'),
            'avg_resolution_hours' => (clone $query)->whereNotNull('resolved_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
                ->value('avg_hours'),
            'teknisi' => User::where('role', 'teknisi')->withCount([
                'assignedTickets as assigned' => fn($q) => $q->whereBetween('created_at', [$dateFrom, $dateTo]),
                'assignedTickets as resolved' => fn($q) => $q->whereIn('status', ['resolved', 'closed'])->whereBetween('created_at', [$dateFrom, $dateTo]),
            ])->get()->map(fn($t) => [
                'id'       => $t->id,
                'name'     => $t->name,
                'assigned' => $t->assigned,
                'resolved' => $t->resolved,
            ]),
        ];

        return response()->json(['data' => $stats]);
    }
}
