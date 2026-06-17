<?php

namespace App\Mail;

use App\Models\TicketRating;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RatingReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public TicketRating $rating) {}

    public function envelope(): Envelope
    {
        $ticketNumber = $this->rating->ticket->ticket_number;
        return new Envelope(
            subject: "[{$ticketNumber}] Rating Diterima: {$this->rating->rating}/5 Bintang",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.ratings.received');
    }

    public function attachments(): array { return []; }
}
