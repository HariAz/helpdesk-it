<?php

namespace App\Services;

use App\Models\Ticket;

class ChannelNotifier
{
    public function __construct(
        private TelegramService  $telegram,
        private WhatsAppService  $whatsapp,
    ) {}

    public function ticketCreated(Ticket $ticket): void
    {
        $text = $this->formatMessage('🎫 Tiket Baru', $ticket, "Prioritas: " . strtoupper($ticket->priority));
        $this->sendAll($text);
    }

    public function ticketAssigned(Ticket $ticket): void
    {
        $assignee = $ticket->assignee?->name ?? 'Belum ditugaskan';
        $text = $this->formatMessage('👤 Tiket Ditugaskan', $ticket, "Teknisi: {$assignee}");
        $this->sendAll($text);
    }

    public function ticketStatusChanged(Ticket $ticket, string $oldStatus, string $newStatus): void
    {
        $labels = \App\Models\Ticket::STATUS_LABELS;
        $from = $labels[$oldStatus] ?? $oldStatus;
        $to   = $labels[$newStatus] ?? $newStatus;
        $text = $this->formatMessage('🔄 Status Berubah', $ticket, "{$from} → {$to}");
        $this->sendAll($text);
    }

    public function ticketResolved(Ticket $ticket): void
    {
        $text = $this->formatMessage('✅ Tiket Diselesaikan', $ticket, "Teknisi: " . ($ticket->assignee?->name ?? '-'));
        $this->sendAll($text);
    }

    public function ticketEscalated(Ticket $ticket): void
    {
        $text = $this->formatMessage('🚨 SLA Terlampaui', $ticket, "Prioritas: " . strtoupper($ticket->priority));
        $this->sendAll($text);
    }

    private function formatMessage(string $title, Ticket $ticket, string $detail): string
    {
        $url = url('/tickets/' . $ticket->id);
        return implode("\n", [
            "<b>{$title}</b>",
            "No: {$ticket->ticket_number}",
            "Judul: {$ticket->title}",
            $detail,
            "Pelapor: {$ticket->user?->name}",
            $url,
        ]);
    }

    private function sendAll(string $text): void
    {
        // Telegram uses HTML parse mode; strip tags for WhatsApp
        $this->telegram->send($text);
        $this->whatsapp->send(strip_tags(str_replace(['<b>', '</b>'], ['*', '*'], $text)));
    }
}
