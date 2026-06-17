<x-mail::message>
# 🎫 Tiket Baru Masuk

Sebuah tiket baru telah dibuat dan memerlukan penanganan segera.

<x-mail::panel>
**No. Tiket:** `{{ $ticket->ticket_number }}`
**Judul:** {{ $ticket->title }}
**Pelapor:** {{ $ticket->user->name }}{{ $ticket->user->department ? ' — ' . $ticket->user->department : '' }}
**Prioritas:** {{ ucfirst($ticket->priority) }}
**Kategori:** {{ $ticket->category?->name ?? 'Tidak ada' }}
**Dibuat:** {{ $ticket->created_at->isoFormat('D MMMM YYYY, HH:mm') }}
**SLA Deadline:** {{ $ticket->sla_deadline?->isoFormat('D MMMM YYYY, HH:mm') ?? '-' }}
</x-mail::panel>

**Deskripsi:**

{{ Str::limit($ticket->description, 500) }}

<x-mail::button :url="route('tickets.show', $ticket)" color="primary">
Lihat & Tangani Tiket
</x-mail::button>

Segera assign tiket ini ke teknisi yang sesuai.

Terima kasih,
{{ config('app.name') }}
</x-mail::message>
