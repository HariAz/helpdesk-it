<?php

namespace App\Jobs;

use App\Mail\RatingReceivedMail;
use App\Models\TicketRating;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendRatingReceivedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public TicketRating $rating) {}

    public function handle(): void
    {
        $this->rating->load(['ticket.assignee', 'ticket.user', 'user']);

        if (!$this->rating->ticket->assignee) {
            return;
        }

        Mail::to($this->rating->ticket->assignee->email)->send(new RatingReceivedMail($this->rating));
    }
}
