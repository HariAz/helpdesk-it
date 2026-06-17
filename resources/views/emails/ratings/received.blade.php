<x-mail::message>
# ⭐ Rating Diterima

Halo **{{ $rating->ticket->assignee?->name ?? 'Teknisi' }}**,

Pengguna telah memberikan penilaian untuk tiket yang Anda tangani.

<x-mail::panel>
**No. Tiket:** `{{ $rating->ticket->ticket_number }}`
**Judul:** {{ $rating->ticket->title }}
**Dinilai oleh:** {{ $rating->user->name }}
**Rating:** {{ str_repeat('⭐', $rating->rating) }} ({{ $rating->rating }}/5)
@if($rating->comment)
**Komentar:** {{ $rating->comment }}
@endif
</x-mail::panel>

@if($rating->rating >= 4)
Terima kasih atas kerja keras Anda! Pengguna sangat puas dengan penanganan tiket ini. 🎉
@elseif($rating->rating === 3)
Terima kasih atas penanganan tiket ini. Tetap tingkatkan kualitas layanan Anda.
@else
Terima kasih atas penanganan tiket ini. Perhatikan feedback dari pengguna untuk meningkatkan kualitas layanan.
@endif

<x-mail::button :url="route('tickets.show', $rating->ticket)" color="primary">
Lihat Detail Tiket
</x-mail::button>

Terima kasih,
{{ config('app.name') }}
</x-mail::message>
