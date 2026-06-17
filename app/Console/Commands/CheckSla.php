<?php

namespace App\Console\Commands;

use App\Jobs\SendSlaEscalationNotification;
use App\Jobs\SendSlaWarningNotification;
use App\Models\Ticket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckSla extends Command
{
    protected $signature = 'helpdesk:check-sla
                            {--notify : Send email notifications}
                            {--dry-run : Preview without making changes}';

    protected $description = 'Check SLA deadlines and escalate overdue tickets';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $notify = $this->option('notify');

        $activeStatuses = ['open', 'assigned', 'in_progress', 'pending_user', 'reopened'];

        $tickets = Ticket::whereIn('status', $activeStatuses)
            ->whereNotNull('sla_deadline')
            ->with(['user', 'assignee'])
            ->get();

        $warned = 0;
        $escalated = 0;

        foreach ($tickets as $ticket) {
            $total = $ticket->sla_deadline->diffInMinutes($ticket->created_at);
            $elapsed = now()->diffInMinutes($ticket->created_at);
            $pct = $total > 0 ? ($elapsed / $total) * 100 : 0;

            if ($pct >= 100 && !$ticket->is_escalated) {
                if (!$dryRun) {
                    $ticket->update(['is_escalated' => true]);
                    if ($notify) {
                        SendSlaEscalationNotification::dispatch($ticket);
                    }
                }
                $escalated++;
                $this->line("[ESKALASI] {$ticket->ticket_number} — {$ticket->title}");
                Log::channel('single')->info("[helpdesk:check-sla] Escalated: {$ticket->ticket_number}");
            } elseif ($pct >= 75 && $pct < 100) {
                if (!$dryRun && $notify) {
                    SendSlaWarningNotification::dispatch($ticket);
                }
                $warned++;
                $this->line("[PERINGATAN] {$ticket->ticket_number} — {$ticket->title} ({$pct}%)");
                Log::channel('single')->info("[helpdesk:check-sla] Warning: {$ticket->ticket_number} at {$pct}%");
            }
        }

        $prefix = $dryRun ? '[DRY RUN] ' : '';
        $this->info("{$prefix}Selesai: {$escalated} eskalasi, {$warned} peringatan dari {$tickets->count()} tiket aktif.");

        return self::SUCCESS;
    }
}
