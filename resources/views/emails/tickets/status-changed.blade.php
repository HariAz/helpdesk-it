<x-mail::message>
# 🔄 Status Tiket Diperbarui

Halo **{{ $ticket->user->name }}**,

Status tiket Anda telah diperbarui oleh tim IT.

<x-mail::panel>
**No. Tiket:** `{{ $ticket->ticket_number }}`
**Judul:** {{ $ticket->title }}
**Status Lama:** {{ \App\Models\Ticket::STATUS_LABELS[$oldStatus] ?? $oldStatus }}
**Status Baru:** {{ \App\Models\Ticket::STATUS_LABELS[$newStatus] ?? $newStatus }}
@if($ticket->assignee)
**Teknisi:** {{ $ticket->assignee->name }}
@endif
**Diperbarui:** {{ now()->isoFormat('D MMMM YYYY, HH:mm') }}
</x-mail::panel>

@if($note)
**Catatan dari Teknisi:**

> {{ $note }}
@endif

@if($newStatus === 'pending_user')
<x-mail::panel>
⚠️ **Aksi Diperlukan:** Teknisi sedang menunggu informasi dari Anda. Silakan buka tiket dan berikan balasan.
</x-mail::panel>
@endif

<x-mail::button :url="route('tickets.show', $ticket)" color="primary">
Lihat Detail Tiket
</x-mail::button>

Terima kasih,
{{ config('app.name') }}
</x-mail::message>
