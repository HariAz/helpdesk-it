<x-mail::message>
# ⚠️ Peringatan SLA: 75% Waktu Terpakai

Tiket berikut telah mencapai **75% batas waktu SLA** dan memerlukan penanganan segera.

<x-mail::panel>
**No. Tiket:** `{{ $ticket->ticket_number }}`
**Judul:** {{ $ticket->title }}
**Prioritas:** {{ ucfirst($ticket->priority) }}
**Pelapor:** {{ $ticket->user->name }}
**Teknisi:** {{ $ticket->assignee?->name ?? 'Belum di-assign' }}
**SLA Deadline:** {{ $ticket->sla_deadline?->isoFormat('D MMMM YYYY, HH:mm') }}
@php
    $remaining = now()->diff($ticket->sla_deadline);
@endphp
**Waktu Tersisa:** {{ $remaining->h }} jam {{ $remaining->i }} menit
</x-mail::panel>

<x-mail::button :url="route('tickets.show', $ticket)" color="error">
Tangani Tiket Sekarang
</x-mail::button>

Jika tiket tidak ditangani sebelum batas waktu, akan terjadi eskalasi otomatis.

Terima kasih,
{{ config('app.name') }}
</x-mail::message>
