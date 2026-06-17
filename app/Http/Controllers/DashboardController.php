<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\User;
use App\Models\ActivityLog;
use Carbon\Carbon;

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

            // Chart: ticket trend last 30 days
            $trendDays = collect(range(29, 0))->map(fn($i) => now()->subDays($i)->format('Y-m-d'));
            $newByDay = Ticket::whereBetween('created_at', [now()->subDays(29)->startOfDay(), now()->endOfDay()])
                ->selectRaw('DATE(created_at) as date, COUNT(*) as cnt')->groupBy('date')->pluck('cnt', 'date');
            $resolvedByDay = Ticket::whereNotNull('resolved_at')
                ->whereBetween('resolved_at', [now()->subDays(29)->startOfDay(), now()->endOfDay()])
                ->selectRaw('DATE(resolved_at) as date, COUNT(*) as cnt')->groupBy('date')->pluck('cnt', 'date');
            $chartTrend = [
                'labels' => $trendDays->map(fn($d) => Carbon::parse($d)->format('d M'))->values()->toArray(),
                'new'    => $trendDays->map(fn($d) => (int)($newByDay[$d] ?? 0))->values()->toArray(),
                'resolved' => $trendDays->map(fn($d) => (int)($resolvedByDay[$d] ?? 0))->values()->toArray(),
            ];

            // Chart: heatmap by day-of-week × hour (last 90 days)
            $heatmapRaw = Ticket::where('created_at', '>=', now()->subDays(89)->startOfDay())
                ->selectRaw('DAYOFWEEK(created_at)-1 as dow, HOUR(created_at) as hr, COUNT(*) as cnt')
                ->groupBy('dow', 'hr')->get();
            $heatmapMax = $heatmapRaw->max('cnt') ?: 1;
            $chartHeatmap = [];
            foreach ($heatmapRaw as $row) {
                $chartHeatmap[(int)$row->dow][(int)$row->hr] = (int)$row->cnt;
            }

            // Chart: category distribution this month
            $categoryPie = Ticket::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->with('category:id,name')
                ->selectRaw('category_id, COUNT(*) as cnt')->groupBy('category_id')->get()
                ->map(fn($r) => ['label' => $r->category?->name ?? 'Tanpa Kategori', 'count' => (int)$r->cnt]);

            return view('dashboard.supervisor', compact(
                'stats', 'nearSla', 'recentActivity', 'topTeknisi',
                'chartTrend', 'chartHeatmap', 'heatmapMax', 'categoryPie'
            ));
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
