<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SlaWarningMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Ticket $ticket) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "⚠️ [{$this->ticket->ticket_number}] SLA 75% — Perlu Perhatian Segera",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.sla.warning');
    }

    public function attachments(): array { return []; }
}
