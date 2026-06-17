<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1e293b; line-height: 1.5; }
    .header { background: #0f172a; color: white; padding: 20px 24px; margin-bottom: 20px; }
    .header h1 { font-size: 18px; font-weight: bold; margin-bottom: 2px; }
    .header p { font-size: 10px; color: #94a3b8; }
    .header-meta { float: right; text-align: right; font-size: 9px; color: #94a3b8; }
    .section { margin-bottom: 18px; }
    .section-title { font-size: 11px; font-weight: bold; color: #0f172a; border-bottom: 2px solid #1a56db; padding-bottom: 4px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: .05em; }
    .stats-grid { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .stats-grid td { width: 25%; padding: 10px 12px; background: #f8fafc; border: 1px solid #e2e8f0; text-align: center; }
    .stats-grid .stat-val { font-size: 20px; font-weight: bold; color: #1a56db; }
    .stats-grid .stat-lbl { font-size: 9px; color: #64748b; margin-top: 2px; }
    table.data { width: 100%; border-collapse: collapse; font-size: 9px; }
    table.data th { background: #f1f5f9; color: #475569; font-weight: bold; padding: 6px 8px; text-align: left; border: 1px solid #e2e8f0; }
    table.data td { padding: 5px 8px; border: 1px solid #e2e8f0; vertical-align: top; }
    table.data tr:nth-child(even) td { background: #f8fafc; }
    .badge { display: inline-block; padding: 2px 6px; border-radius: 10px; font-size: 8px; font-weight: bold; }
    .badge-danger { background: #fee2e2; color: #991b1b; }
    .badge-warning { background: #fef3c7; color: #92400e; }
    .badge-info { background: #dbeafe; color: #1e40af; }
    .badge-secondary { background: #f1f5f9; color: #475569; }
    .badge-success { background: #dcfce7; color: #166534; }
    .progress-wrap { background: #e2e8f0; border-radius: 4px; height: 6px; width: 80px; display: inline-block; vertical-align: middle; }
    .progress-fill { height: 6px; border-radius: 4px; background: #1a56db; }
    .progress-fill-ok { background: #16a34a; }
    .progress-fill-warn { background: #d97706; }
    .progress-fill-bad { background: #dc2626; }
    .row2 { width: 100%; border-collapse: collapse; }
    .row2 td { vertical-align: top; padding-right: 12px; }
    .row2 td:last-child { padding-right: 0; }
    .footer { margin-top: 24px; padding-top: 10px; border-top: 1px solid #e2e8f0; font-size: 8px; color: #94a3b8; text-align: center; }
    .clearfix::after { content: ''; display: table; clear: both; }
    .text-green { color: #16a34a; }
    .text-red { color: #dc2626; }
    .text-muted { color: #64748b; }
</style>
</head>
<body>

<div class="header clearfix">
    <div class="header-meta">
        Dicetak: {{ now()->format('d/m/Y H:i') }}<br>
        Halaman 1
    </div>
    <h1>Laporan Helpdesk IT</h1>
    <p>Periode: {{ $monthLabel }}</p>
</div>

<!-- Summary Stats -->
<div class="section">
    <div class="section-title">Ringkasan</div>
    <table class="stats-grid">
        <tr>
            <td>
                <div class="stat-val">{{ $stats['total'] }}</div>
                <div class="stat-lbl">Total Tiket</div>
            </td>
            <td>
                <div class="stat-val" style="color:#16a34a;">{{ $stats['resolved'] }}</div>
                <div class="stat-lbl">Diselesaikan</div>
            </td>
            <td>
                <div class="stat-val" style="color:#d97706;">{{ $stats['open'] }}</div>
                <div class="stat-lbl">Masih Aktif</div>
            </td>
            <td>
                <div class="stat-val" style="color:#dc2626;">{{ $stats['escalated'] }}</div>
                <div class="stat-lbl">SLA Terlampaui</div>
            </td>
        </tr>
    </table>
    @if($stats['total'] > 0)
    <p style="font-size:9px; color:#64748b;">
        Tingkat resolusi: <strong>{{ $stats['total'] > 0 ? round(($stats['resolved']/$stats['total'])*100) : 0 }}%</strong>
        &nbsp;·&nbsp;
        Rata-rata waktu resolusi: <strong>{{ $stats['avg_hours'] ? round($stats['avg_hours'], 1) . ' jam' : '—' }}</strong>
    </p>
    @endif
</div>

<!-- Priority + Status side by side -->
<table class="row2">
<tr>
<td style="width:50%;">
    <div class="section">
        <div class="section-title">Per Prioritas</div>
        <table class="data">
            <tr><th>Prioritas</th><th>Jumlah</th><th>%</th></tr>
            @foreach(['kritis' => 'Kritis', 'tinggi' => 'Tinggi', 'sedang' => 'Sedang', 'rendah' => 'Rendah'] as $p => $lbl)
            @php $cnt = $byPriority[$p] ?? 0; @endphp
            <tr>
                <td>{{ $lbl }}</td>
                <td>{{ $cnt }}</td>
                <td>{{ $stats['total'] > 0 ? round(($cnt/$stats['total'])*100) : 0 }}%</td>
            </tr>
            @endforeach
        </table>
    </div>
</td>
<td style="width:50%; padding-left:12px; padding-right:0;">
    <div class="section">
        <div class="section-title">Per Status</div>
        <table class="data">
            <tr><th>Status</th><th>Jumlah</th></tr>
            @foreach(\App\Models\Ticket::STATUS_LABELS as $s => $lbl)
            @php $cnt = $byStatus[$s] ?? 0; if(!$cnt) continue; @endphp
            <tr><td>{{ $lbl }}</td><td>{{ $cnt }}</td></tr>
            @endforeach
        </table>
    </div>
</td>
</tr>
</table>

<!-- Category breakdown -->
@if($byCategory->count())
<div class="section">
    <div class="section-title">Per Kategori</div>
    <table class="data">
        <tr><th>Kategori</th><th>Jumlah</th><th>Porsi</th></tr>
        @foreach($byCategory->sortByDesc('count') as $cat)
        @php $pct = $stats['total'] > 0 ? round(($cat->count/$stats['total'])*100) : 0; @endphp
        <tr>
            <td>{{ $cat->category?->name ?? 'Tanpa Kategori' }}</td>
            <td>{{ $cat->count }}</td>
            <td>
                <div class="progress-wrap" style="display:inline-block;">
                    <div class="progress-fill" style="width:{{ $pct }}%;"></div>
                </div>
                {{ $pct }}%
            </td>
        </tr>
        @endforeach
    </table>
</div>
@endif

<!-- Teknisi Performance -->
<div class="section">
    <div class="section-title">Performa Teknisi</div>
    <table class="data">
        <tr><th>Teknisi</th><th>Ditugaskan</th><th>Selesai</th><th>% Selesai</th></tr>
        @forelse($teknisiStats as $tek)
        @php $pct = $tek->total_assigned > 0 ? round(($tek->resolved_count/$tek->total_assigned)*100) : 0; @endphp
        <tr>
            <td>{{ $tek->name }}</td>
            <td>{{ $tek->total_assigned }}</td>
            <td class="text-green">{{ $tek->resolved_count }}</td>
            <td>{{ $tek->total_assigned > 0 ? $pct.'%' : '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="4" class="text-muted">Tidak ada data</td></tr>
        @endforelse
    </table>
</div>

<!-- SLA Compliance -->
@if($slaCompliance->count())
<div class="section">
    <div class="section-title">SLA Compliance per Teknisi</div>
    <table class="data">
        <tr><th>Teknisi</th><th>Total Selesai</th><th>Tepat Waktu</th><th>Terlambat</th><th>Compliance</th></tr>
        @foreach($slaCompliance as $row)
        @php
            $rate = $row['rate'] ?? 0;
            $cls = $rate >= 90 ? 'progress-fill-ok' : ($rate >= 70 ? 'progress-fill-warn' : 'progress-fill-bad');
            $tcls = $rate >= 90 ? 'text-green' : ($rate < 70 ? 'text-red' : '');
        @endphp
        <tr>
            <td>{{ $row['name'] }}</td>
            <td>{{ $row['total'] }}</td>
            <td class="text-green">{{ $row['on_time'] }}</td>
            <td class="{{ $row['late'] > 0 ? 'text-red' : '' }}">{{ $row['late'] }}</td>
            <td>
                @if($row['rate'] !== null)
                <div class="progress-wrap" style="display:inline-block;"><div class="{{ $cls }}" style="width:{{ $rate }}%; height:6px; border-radius:4px;"></div></div>
                <span class="{{ $tcls }}"> {{ $rate }}%</span>
                @else
                <span class="text-muted">—</span>
                @endif
            </td>
        </tr>
        @endforeach
    </table>
</div>
@endif

<div class="footer">
    Laporan ini dibuat otomatis oleh Sistem Helpdesk IT &nbsp;·&nbsp; {{ now()->format('d M Y H:i') }}
</div>

</body>
</html>
