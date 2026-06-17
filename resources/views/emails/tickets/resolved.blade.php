<x-mail::message>
# ✅ Tiket Anda Telah Diselesaikan

Halo **{{ $ticket->user->name }}**,

Tiket Anda telah berhasil diselesaikan oleh tim IT. Kami harap masalah Anda sudah teratasi.

<x-mail::panel>
**No. Tiket:** `{{ $ticket->ticket_number }}`
**Judul:** {{ $ticket->title }}
**Diselesaikan oleh:** {{ $ticket->assignee?->name ?? 'Tim IT' }}
**Tanggal Selesai:** {{ $ticket->resolved_at?->isoFormat('D MMMM YYYY, HH:mm') ?? now()->isoFormat('D MMMM YYYY, HH:mm') }}
</x-mail::panel>

## Berikan Penilaian Anda ⭐

Luangkan waktu sebentar untuk menilai kualitas penanganan tiket ini. Penilaian Anda sangat berarti untuk meningkatkan layanan IT kami.

<x-mail::button :url="route('rating.show', $ratingToken->token)" color="success">
Beri Penilaian (1–5 Bintang)
</x-mail::button>

> ℹ️ Link penilaian ini berlaku selama **7 hari** dan hanya dapat digunakan sekali.

Jika masalah Anda belum teratasi sepenuhnya, Anda dapat membuka kembali tiket melalui tombol di bawah.

<x-mail::button :url="route('tickets.show', $ticket)" color="primary">
Lihat Detail Tiket
</x-mail::button>

Terima kasih telah menggunakan layanan Helpdesk IT,
{{ config('app.name') }}
</x-mail::message>
