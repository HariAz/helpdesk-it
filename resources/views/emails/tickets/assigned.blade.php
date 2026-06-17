<x-mail::message>
# 📋 Tiket Baru Di-assign ke Anda

Halo **{{ $ticket->assignee->name }}**,

Sebuah tiket telah di-assign kepada Anda dan memerlukan penanganan.

<x-mail::panel>
**No. Tiket:** `{{ $ticket->ticket_number }}`
**Judul:** {{ $ticket->title }}
**Pelapor:** {{ $ticket->user->name }}{{ $ticket->user->phone ? ' (HP: ' . $ticket->user->phone . ')' : '' }}
**Prioritas:** {{ ucfirst($ticket->priority) }}
**Kategori:** {{ $ticket->category?->name ?? '-' }}
**SLA Deadline:** {{ $ticket->sla_deadline?->isoFormat('D MMMM YYYY, HH:mm') ?? '-' }}
</x-mail::panel>

**Deskripsi Masalah:**

{{ Str::limit($ticket->description, 500) }}

<x-mail::button :url="route('tickets.show', $ticket)" color="primary">
Mulai Tangani Tiket
</x-mail::button>

Pastikan Anda menangani tiket ini sesuai SLA yang ditetapkan.

Terima kasih,
{{ config('app.name') }}
</x-mail::message>
