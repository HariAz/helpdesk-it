<x-mail::message>
# 💬 Komentar Baru pada Tiket Anda

Terdapat komentar baru pada tiket **{{ $ticket->ticket_number }}**.

<x-mail::panel>
**No. Tiket:** `{{ $ticket->ticket_number }}`
**Judul:** {{ $ticket->title }}
**Komentar dari:** {{ $comment->user->name }} ({{ ucfirst($comment->user->role) }})
**Waktu:** {{ $comment->created_at->isoFormat('D MMMM YYYY, HH:mm') }}
</x-mail::panel>

**Isi Komentar:**

> {{ Str::limit($comment->body, 500) }}

<x-mail::button :url="route('tickets.show', $ticket)" color="primary">
Balas Komentar
</x-mail::button>

Terima kasih,
{{ config('app.name') }}
</x-mail::message>
