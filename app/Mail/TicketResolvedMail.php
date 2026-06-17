<?php

namespace App\Mail;

use App\Models\Ticket;
use App\Models\RatingToken;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketResolvedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public RatingToken $ratingToken
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[{$this->ticket->ticket_number}] Tiket Anda Telah Diselesaikan",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.tickets.resolved');
    }

    public function attachments(): array { return []; }
}
