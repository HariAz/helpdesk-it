<?php

namespace App\Jobs;

use App\Mail\NewTicketMail;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ChannelNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendNewTicketNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public Ticket $ticket) {}

    public function handle(): void
    {
        $supervisors = User::where('role', 'supervisor')->where('is_active', true)->get();
        foreach ($supervisors as $supervisor) {
            Mail::to($supervisor->email)->send(new NewTicketMail($this->ticket));
        }
        app(ChannelNotifier::class)->ticketCreated($this->ticket);
    }
}
