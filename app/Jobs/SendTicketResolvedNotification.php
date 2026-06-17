<?php

namespace App\Jobs;

use App\Mail\TicketResolvedMail;
use App\Models\RatingToken;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendTicketResolvedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public Ticket $ticket) {}

    public function handle(): void
    {
        $token = RatingToken::where('ticket_id', $this->ticket->id)
            ->where('is_used', false)
            ->latest()
            ->first();

        if (!$token) {
            return;
        }

        Mail::to($this->ticket->user->email)->send(new TicketResolvedMail($this->ticket, $token));
    }
}
