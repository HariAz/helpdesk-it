<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Models\User;
use App\Mail\WeeklyReportMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendWeeklyReport extends Command
{
    protected $signature = 'helpdesk:send-weekly-report {--dry-run : Print stats without sending email}';
    protected $description = 'Send weekly helpdesk summary report to all supervisors';

    public function handle(): int
    {
        $from = now()->subDays(7)->startOfDay();
        $to   = now()->subDay()->endOfDay();

        $weekLabel = $from->isoFormat('D MMM') . ' – ' . $to->isoFormat('D MMM YYYY');

        $newTickets      = Ticket::whereBetween('created_at', [$from, $to])->count();
        $resolvedTickets = Ticket::whereNotNull('resolved_at')->whereBetween('resolved_at', [$from, $to])->count();
        $activeTickets   = Ticket::whereNotIn('status', ['closed', 'cancelled'])->count();
        $escalated       = Ticket::where('is_escalated', true)->whereBetween('created_at', [$from, $to])->count();
        $unassigned      = Ticket::whereNull('assigned_to')->whereNotIn('status', ['closed', 'cancelled'])->count();
        $avgHours        = Ticket::whereNotNull('resolved_at')->whereBetween('resolved_at', [$from, $to])
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
            ->value('avg_hours');

        // SLA compliance for the week
        $weekResolved = Ticket::whereNotNull('resolved_at')->whereNotNull('sla_deadline')
            ->whereBetween('resolved_at', [$from, $to])->get(['resolved_at', 'sla_deadline']);
        $weekTotal  = $weekResolved->count();
        $weekOnTime = $weekResolved->filter(fn($t) => $t->resolved_at->lte($t->sla_deadline))->count();
        $slaRate    = $weekTotal > 0 ? round(($weekOnTime / $weekTotal) * 100) : null;

        // Per-teknisi stats
        $teknisiStats = User::where('role', 'teknisi')->get()->map(function ($tek) use ($from, $to) {
            $total    = Ticket::where('assigned_to', $tek->id)->whereBetween('created_at', [$from, $to])->count();
            $resolved = Ticket::where('assigned_to', $tek->id)->whereNotNull('resolved_at')
                ->whereBetween('resolved_at', [$from, $to])->count();
            return ['name' => $tek->name, 'total' => $total, 'resolved' => $resolved];
        })->filter(fn($r) => $r['total'] > 0 || $r['resolved'] > 0)->values()->toArray();

        $byPriority = Ticket::whereBetween('created_at', [$from, $to])
            ->selectRaw('priority, COUNT(*) as cnt')->groupBy('priority')
            ->pluck('cnt', 'priority')->toArray();

        $stats = [
            'week_label'          => $weekLabel,
            'new_tickets'         => $newTickets,
            'resolved_tickets'    => $resolvedTickets,
            'active_tickets'      => $activeTickets,
            'escalated_tickets'   => $escalated,
            'unassigned'          => $unassigned,
            'avg_resolution_hours' => $avgHours,
            'sla_compliance_rate'  => $slaRate,
            'teknisi_stats'        => $teknisiStats,
            'by_priority'          => $byPriority,
        ];

        if ($this->option('dry-run')) {
            $this->table(['Metric', 'Value'], array_map(
                fn($k, $v) => [$k, is_array($v) ? json_encode($v) : $v],
                array_keys($stats), $stats
            ));
            return self::SUCCESS;
        }

        $supervisors = User::where('role', 'supervisor')->where('is_active', true)->get();

        if ($supervisors->isEmpty()) {
            $this->warn('No active supervisors found.');
            return self::SUCCESS;
        }

        foreach ($supervisors as $supervisor) {
            Mail::to($supervisor->email)->queue(new WeeklyReportMail($stats));
        }

        Log::info("helpdesk:send-weekly-report sent to {$supervisors->count()} supervisors. Week: {$weekLabel}");
        $this->info("Weekly report sent to {$supervisors->count()} supervisors.");

        return self::SUCCESS;
    }
}
