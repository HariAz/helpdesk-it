<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Models\TicketStatusLog;
use Illuminate\Console\Command;

class AutoCloseTickets extends Command
{
    protected $signature = 'helpdesk:auto-close';

    protected $description = 'Auto-close resolved tickets that have not been rated after 3 days';

    public function handle(): int
    {
        $tickets = Ticket::where('status', 'resolved')
            ->whereNull('closed_at')
            ->where('resolved_at', '<=', now()->subDays(3))
            ->whereDoesntHave('rating')
            ->get();

        $count = 0;

        foreach ($tickets as $ticket) {
            $ticket->update(['status' => 'closed', 'closed_at' => now()]);

            TicketStatusLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => null,
                'from_status' => 'resolved',
                'to_status' => 'closed',
                'note' => 'Ditutup otomatis setelah 3 hari tanpa penilaian.',
            ]);

            ActivityLog::record('ticket_auto_closed', $ticket, ['reason' => 'no_rating_after_3_days']);

            $count++;
            $this->line("Closed: {$ticket->ticket_number}");
        }

        $this->info("Auto-close selesai: {$count} tiket ditutup.");

        return self::SUCCESS;
    }
}
