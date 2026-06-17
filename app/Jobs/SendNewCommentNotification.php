<?php

namespace App\Jobs;

use App\Mail\NewCommentMail;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendNewCommentNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public Ticket $ticket,
        public TicketComment $comment
    ) {}

    public function handle(): void
    {
        $recipients = collect();

        // Notify ticket owner (unless they wrote the comment)
        if ($this->comment->user_id !== $this->ticket->user_id) {
            $recipients->push($this->ticket->user);
        }

        // Notify assigned teknisi (unless they wrote the comment, and skip for internal notes to user)
        if ($this->ticket->assignee && $this->comment->user_id !== $this->ticket->assigned_to) {
            if (!$this->comment->is_internal || $this->ticket->assignee->role !== 'user') {
                $recipients->push($this->ticket->assignee);
            }
        }

        // For internal notes, notify supervisors too
        if ($this->comment->is_internal) {
            $supervisors = User::where('role', 'supervisor')->where('is_active', true)->get();
            foreach ($supervisors as $supervisor) {
                if ($this->comment->user_id !== $supervisor->id) {
                    $recipients->push($supervisor);
                }
            }
        }

        $recipients->unique('id')->each(function (User $recipient) {
            Mail::to($recipient->email)->send(new NewCommentMail($this->ticket, $this->comment));
        });
    }
}
