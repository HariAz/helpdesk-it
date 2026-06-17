<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailySummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $stats) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "📊 Ringkasan Harian Helpdesk — " . now()->isoFormat('D MMMM YYYY'),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.summary.daily');
    }

    public function attachments(): array { return []; }
}
