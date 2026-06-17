<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f1f5f9; margin: 0; padding: 20px; }
    .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,.07); }
    .header { background: linear-gradient(135deg, #0f172a 0%, #1a56db 100%); padding: 28px 32px; color: white; }
    .header h1 { font-size: 20px; font-weight: 700; margin: 0 0 4px; }
    .header p { font-size: 13px; opacity: .7; margin: 0; }
    .body { padding: 28px 32px; }
    .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 24px; }
    .stat-box { background: #f8fafc; border-radius: 10px; padding: 14px 16px; border: 1px solid #e2e8f0; }
    .stat-value { font-size: 26px; font-weight: 700; color: #0f172a; line-height: 1; }
    .stat-label { font-size: 11px; color: #64748b; margin-top: 4px; }
    .stat-value.green { color: #16a34a; }
    .stat-value.red { color: #dc2626; }
    .stat-value.blue { color: #1a56db; }
    .section-title { font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 12px; padding-bottom: 6px; border-bottom: 2px solid #e2e8f0; }
    table { width: 100%; border-collapse: collapse; font-size: 12px; margin-bottom: 24px; }
    th { background: #f8fafc; font-weight: 600; color: #475569; padding: 8px 10px; text-align: left; border-bottom: 1px solid #e2e8f0; }
    td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; color: #374151; }
    .progress-wrap { display: inline-block; background: #e2e8f0; border-radius: 4px; height: 6px; width: 70px; vertical-align: middle; }
    .progress-fill { height: 6px; border-radius: 4px; background: #1a56db; }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: 600; }
    .badge-green { background: #dcfce7; color: #166534; }
    .badge-red { background: #fee2e2; color: #991b1b; }
    .badge-blue { background: #dbeafe; color: #1e40af; }
    .btn { display: inline-block; background: #1a56db; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 13px; margin-top: 8px; }
    .footer { background: #f8fafc; padding: 16px 32px; font-size: 11px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; }
    .highlight-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; }
    .highlight-box.warn { background: #fefce8; border-color: #fde68a; }
    .highlight-box.danger { background: #fef2f2; border-color: #fecaca; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📊 Laporan Mingguan Helpdesk IT</h1>
        <p>Periode: {{ $stats['week_label'] }}</p>
    </div>
    <div class="body">
        <p style="font-size:13px; color:#374151; margin-bottom:20px;">
            Berikut adalah ringkasan aktivitas Helpdesk IT selama 7 hari terakhir.
        </p>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-value blue">{{ $stats['new_tickets'] }}</div>
                <div class="stat-label">Tiket Baru</div>
            </div>
            <div class="stat-box">
                <div class="stat-value green">{{ $stats['resolved_tickets'] }}</div>
                <div class="stat-label">Diselesaikan</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">{{ $stats['active_tickets'] }}</div>
                <div class="stat-label">Masih Aktif</div>
            </div>
            <div class="stat-box">
                <div class="stat-value red">{{ $stats['escalated_tickets'] }}</div>
                <div class="stat-label">SLA Terlampaui</div>
            </div>
        </div>

        @if($stats['avg_resolution_hours'])
        <div class="highlight-box">
            <strong style="font-size:12px;">⏱ Rata-rata Waktu Resolusi:</strong>
            <span style="font-size:12px; color:#374151;"> {{ round($stats['avg_resolution_hours'], 1) }} jam</span>
        </div>
        @endif

        @if($stats['sla_compliance_rate'] !== null)
        @php $rate = $stats['sla_compliance_rate']; @endphp
        <div class="highlight-box {{ $rate >= 90 ? '' : ($rate >= 70 ? 'warn' : 'danger') }}">
            <strong style="font-size:12px;">
                {{ $rate >= 90 ? '✅' : ($rate >= 70 ? '⚠️' : '❌') }}
                SLA Compliance Minggu Ini:
            </strong>
            <span style="font-size:15px; font-weight:700; color:{{ $rate >= 90 ? '#16a34a' : ($rate >= 70 ? '#d97706' : '#dc2626') }};">
                {{ $rate }}%
            </span>
        </div>
        @endif

        <!-- Teknisi Performance -->
        @if(count($stats['teknisi_stats']) > 0)
        <div class="section-title">Performa Teknisi</div>
        <table>
            <tr>
                <th>Teknisi</th>
                <th>Ditugaskan</th>
                <th>Selesai</th>
                <th>Progress</th>
            </tr>
            @foreach($stats['teknisi_stats'] as $tek)
            @php $pct = $tek['total'] > 0 ? round(($tek['resolved']/$tek['total'])*100) : 0; @endphp
            <tr>
                <td><strong>{{ $tek['name'] }}</strong></td>
                <td>{{ $tek['total'] }}</td>
                <td>{{ $tek['resolved'] }}</td>
                <td>
                    <div class="progress-wrap"><div class="progress-fill" style="width:{{ $pct }}%;"></div></div>
                    <span style="margin-left:6px; font-size:11px;">{{ $pct }}%</span>
                </td>
            </tr>
            @endforeach
        </table>
        @endif

        <!-- Priority breakdown -->
        @if(count($stats['by_priority']) > 0)
        <div class="section-title">Distribusi Prioritas</div>
        <table>
            <tr><th>Prioritas</th><th>Jumlah</th></tr>
            @foreach($stats['by_priority'] as $p => $cnt)
            <tr>
                <td>
                    <span class="badge badge-{{ $p === 'kritis' ? 'red' : ($p === 'tinggi' ? 'red' : ($p === 'sedang' ? 'blue' : 'green')) }}">
                        {{ ucfirst($p) }}
                    </span>
                </td>
                <td><strong>{{ $cnt }}</strong></td>
            </tr>
            @endforeach
        </table>
        @endif

        @if($stats['unassigned'] > 0)
        <div class="highlight-box danger">
            <strong style="font-size:12px;">⚠️ Tiket Belum Ditugaskan: {{ $stats['unassigned'] }}</strong><br>
            <span style="font-size:11px; color:#dc2626;">Segera tugaskan ke teknisi yang tersedia.</span>
        </div>
        @endif

        <div style="text-align:center; margin-top:24px;">
            <a href="{{ url('/dashboard') }}" class="btn">Buka Dashboard</a>
        </div>
    </div>
    <div class="footer">
        Email ini dikirim otomatis setiap Senin pukul 07:00 oleh Sistem Helpdesk IT.<br>
        Dikirim pada {{ now()->isoFormat('dddd, D MMMM YYYY HH:mm') }}
    </div>
</div>
</body>
</html>
