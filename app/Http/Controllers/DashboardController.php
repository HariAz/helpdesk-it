<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\User;
use App\Models\ActivityLog;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->isSupervisor()) {
            $stats = [
                'total' => Ticket::count(),
                'active' => Ticket::whereNotIn('status', ['closed', 'cancelled'])->count(),
                'escalated' => Ticket::where('is_escalated', true)->count(),
                'avg_resolution' => Ticket::whereNotNull('resolved_at')
                    ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
                    ->value('avg_hours'),
            ];

            $nearSla = Ticket::whereNotIn('status', ['resolved', 'closed', 'cancelled'])
                ->whereNotNull('sla_deadline')
                ->orderBy('sla_deadline')
                ->limit(10)
                ->with(['user', 'assignee'])
                ->get();

            $recentActivity = ActivityLog::with('user')->orderByDesc('created_at')->limit(10)->get();

            $topTeknisi = User::where('role', 'teknisi')
                ->withCount(['assignedTickets as resolved_count' => fn($q) => $q->where('status', 'closed')])
                ->orderByDesc('resolved_count')
                ->limit(5)
                ->get();

            return view('dashboard.supervisor', compact('stats', 'nearSla', 'recentActivity', 'topTeknisi'));
        }

        if ($user->isTeknisi()) {
            $myTickets = Ticket::where('assigned_to', $user->id)
                ->whereNotIn('status', ['closed', 'cancelled'])
                ->with(['user', 'category'])
                ->orderByRaw("FIELD(priority, 'kritis', 'tinggi', 'sedang', 'rendah')")
                ->get();

            $stats = [
                'active' => $myTickets->count(),
                'done_today' => Ticket::where('assigned_to', $user->id)
                    ->whereDate('resolved_at', today())->count(),
                'escalated' => Ticket::where('assigned_to', $user->id)->where('is_escalated', true)->count(),
                'pending_user' => Ticket::where('assigned_to', $user->id)->where('status', 'pending_user')->count(),
            ];

            return view('dashboard.teknisi', compact('myTickets', 'stats'));
        }

        // User role
        $activeTickets = Ticket::where('user_id', $user->id)
            ->whereNotIn('status', ['closed', 'cancelled'])
            ->with('category')
            ->orderByDesc('created_at')
            ->get();

        $closedTickets = Ticket::where('user_id', $user->id)
            ->whereIn('status', ['closed', 'cancelled'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('dashboard.user', compact('activeTickets', 'closedTickets'));
    }
}
