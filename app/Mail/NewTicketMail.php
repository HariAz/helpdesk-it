<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewTicketMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public \App\Models\Ticket $ticket) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[{$this->ticket->ticket_number}] Tiket Baru: {$this->ticket->title}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.tickets.new');
    }

    public function attachments(): array { return []; }
}
