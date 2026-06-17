<x-mail::message>
# 🔴 ESKALASI: SLA Telah Terlampaui

**PERHATIAN:** Tiket berikut telah **melampaui batas waktu SLA** dan memerlukan tindakan segera.

<x-mail::panel>
**No. Tiket:** `{{ $ticket->ticket_number }}`
**Judul:** {{ $ticket->title }}
**Prioritas:** {{ ucfirst($ticket->priority) }}
**Pelapor:** {{ $ticket->user->name }}
**Teknisi:** {{ $ticket->assignee?->name ?? '⚠️ Belum di-assign!' }}
**SLA Deadline:** {{ $ticket->sla_deadline?->isoFormat('D MMMM YYYY, HH:mm') }}
@php
    $overdue = $ticket->sla_deadline ? now()->diff($ticket->sla_deadline) : null;
@endphp
@if($overdue)
**Keterlambatan:** {{ $overdue->h }} jam {{ $overdue->i }} menit
@endif
**Status Saat Ini:** {{ \App\Models\Ticket::STATUS_LABELS[$ticket->status] ?? $ticket->status }}
</x-mail::panel>

Tiket ini telah ditandai sebagai **eskalasi** dalam sistem.

<x-mail::button :url="route('tickets.show', $ticket)" color="error">
Tangani Eskalasi Sekarang
</x-mail::button>

Segera koordinasikan penanganan tiket ini dengan teknisi terkait.

Terima kasih,
{{ config('app.name') }}
</x-mail::message>
