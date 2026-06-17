<?php

namespace App\Jobs;

use App\Mail\TicketStatusChangedMail;
use App\Models\Ticket;
use App\Services\ChannelNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendTicketStatusChangedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public Ticket $ticket,
        public string $oldStatus,
        public string $newStatus,
        public string $note = ''
    ) {}

    public function handle(): void
    {
        // Notify ticket owner
        Mail::to($this->ticket->user->email)->send(
            new TicketStatusChangedMail($this->ticket, $this->oldStatus, $this->newStatus, $this->note)
        );

        // Notify assigned teknisi if status changed by supervisor/user
        if ($this->ticket->assignee && $this->ticket->assignee->id !== $this->ticket->user->id) {
            Mail::to($this->ticket->assignee->email)->send(
                new TicketStatusChangedMail($this->ticket, $this->oldStatus, $this->newStatus, $this->note)
            );
        }
        app(ChannelNotifier::class)->ticketStatusChanged($this->ticket, $this->oldStatus, $this->newStatus);
    }
}
