<?php

namespace App\Jobs;

use App\Mail\TicketAssignedMail;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendTicketAssignedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public Ticket $ticket) {}

    public function handle(): void
    {
        if (!$this->ticket->assignee) {
            return;
        }
        Mail::to($this->ticket->assignee->email)->send(new TicketAssignedMail($this->ticket));
        // Also notify the ticket owner
        Mail::to($this->ticket->user->email)->send(new TicketAssignedMail($this->ticket));
    }
}
