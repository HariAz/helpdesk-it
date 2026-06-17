<?php

namespace App\Console\Commands;

use App\Jobs\SendDailySummaryNotification;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Console\Command;

class SendDailySummary extends Command
{
    protected $signature = 'helpdesk:send-summary';

    protected $description = 'Send daily helpdesk summary to all supervisors';

    public function handle(): int
    {
        $today = today();

        $newToday = Ticket::whereDate('created_at', $today)->count();
        $resolvedToday = Ticket::whereDate('resolved_at', $today)->count();
        $totalActive = Ticket::whereNotIn('status', ['closed', 'cancelled'])->count();
        $escalated = Ticket::where('is_escalated', true)
            ->whereNotIn('status', ['closed', 'cancelled', 'resolved'])->count();
        $unassigned = Ticket::whereNull('assigned_to')
            ->whereNotIn('status', ['closed', 'cancelled', 'resolved'])->count();

        $avgResolutionHours = Ticket::whereDate('resolved_at', $today)
            ->whereNotNull('created_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
            ->value('avg_hours');

        $topTeknisi = User::where('role', 'teknisi')
            ->withCount(['tickets as resolved_count' => function ($q) use ($today) {
                $q->whereDate('resolved_at', $today);
            }])
            ->orderByDesc('resolved_count')
            ->having('resolved_count', '>', 0)
            ->limit(5)
            ->get()
            ->map(fn($u) => ['name' => $u->name, 'resolved' => $u->resolved_count])
            ->toArray();

        $stats = [
            'new_today' => $newToday,
            'resolved_today' => $resolvedToday,
            'total_active' => $totalActive,
            'escalated' => $escalated,
            'unassigned' => $unassigned,
            'avg_resolution_hours' => $avgResolutionHours,
            'top_teknisi' => $topTeknisi,
        ];

        SendDailySummaryNotification::dispatch($stats);

        $this->info("Daily summary dispatched. Stats: {$newToday} new, {$resolvedToday} resolved, {$escalated} escalated.");

        return self::SUCCESS;
    }
}
