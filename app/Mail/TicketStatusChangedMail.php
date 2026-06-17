<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public string $oldStatus,
        public string $newStatus,
        public string $note = ''
    ) {}

    public function envelope(): Envelope
    {
        $label = \App\Models\Ticket::STATUS_LABELS[$this->newStatus] ?? $this->newStatus;
        return new Envelope(
            subject: "[{$this->ticket->ticket_number}] Status Tiket Diperbarui: {$label}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.tickets.status-changed');
    }

    public function attachments(): array { return []; }
}
