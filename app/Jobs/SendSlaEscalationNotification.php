<?php

namespace App\Jobs;

use App\Mail\SlaEscalationMail;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendSlaEscalationNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public Ticket $ticket) {}

    public function handle(): void
    {
        // Notify assigned teknisi
        if ($this->ticket->assignee) {
            Mail::to($this->ticket->assignee->email)->send(new SlaEscalationMail($this->ticket));
        }

        // Notify all supervisors
        $supervisors = User::where('role', 'supervisor')->where('is_active', true)->get();
        foreach ($supervisors as $supervisor) {
            Mail::to($supervisor->email)->send(new SlaEscalationMail($this->ticket));
        }

        // Also notify ticket owner about escalation
        Mail::to($this->ticket->user->email)->send(new SlaEscalationMail($this->ticket));
    }
}
