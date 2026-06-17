<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $stats) {}

    public function envelope(): Envelope
    {
        $week = now()->subDays(7)->format('d M') . ' – ' . now()->subDay()->format('d M Y');
        return new Envelope(subject: "Laporan Mingguan Helpdesk IT ({$week})");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.reports.weekly');
    }
}
