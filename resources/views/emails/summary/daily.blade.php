<x-mail::message>
# 📊 Ringkasan Harian Helpdesk

**{{ now()->isoFormat('dddd, D MMMM YYYY') }}**

Berikut ringkasan aktivitas helpdesk dalam 24 jam terakhir.

<x-mail::panel>
| Metrik | Nilai |
|--------|-------|
| Tiket Masuk Hari Ini | {{ $stats['new_today'] }} |
| Tiket Diselesaikan Hari Ini | {{ $stats['resolved_today'] }} |
| Total Tiket Aktif | {{ $stats['total_active'] }} |
| Tiket Eskalasi | {{ $stats['escalated'] }} |
| Menunggu Penugasan | {{ $stats['unassigned'] }} |
| Avg. Waktu Resolusi (jam) | {{ $stats['avg_resolution_hours'] ? round($stats['avg_resolution_hours'], 1) : '-' }} |
</x-mail::panel>

@if($stats['escalated'] > 0)
<x-mail::panel>
⚠️ **Perhatian:** Terdapat **{{ $stats['escalated'] }} tiket eskalasi** yang memerlukan tindakan segera.
</x-mail::panel>
@endif

@if(!empty($stats['top_teknisi']))
## Performa Teknisi Hari Ini

@foreach($stats['top_teknisi'] as $tek)
- **{{ $tek['name'] }}**: {{ $tek['resolved'] }} tiket diselesaikan
@endforeach
@endif

<x-mail::button :url="route('dashboard')" color="primary">
Buka Dashboard
</x-mail::button>

Terima kasih,
{{ config('app.name') }}
</x-mail::message>
