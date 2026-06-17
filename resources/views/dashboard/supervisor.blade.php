@extends('layouts.app')

@section('title', 'Dashboard Supervisor')

@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
<div class="d-flex align-items-start justify-content-between mb-4">
    <div>
        <h1 class="page-title">Dashboard Supervisor</h1>
        <p class="page-subtitle">Selamat datang, {{ auth()->user()->name }}. Berikut ringkasan sistem hari ini.</p>
    </div>
    <a href="{{ route('reports.index') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-bar-chart-line me-1"></i> Lihat Laporan
    </a>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-ticket-detailed"></i>
                </div>
                <div>
                    <div class="stat-value">{{ $stats['total'] }}</div>
                    <div class="stat-label">Total Tiket</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <div class="stat-value">{{ $stats['active'] }}</div>
                    <div class="stat-label">Tiket Aktif</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div>
                    <div class="stat-value text-danger">{{ $stats['escalated'] }}</div>
                    <div class="stat-label">SLA Terlampaui</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-clock"></i>
                </div>
                <div>
                    <div class="stat-value">{{ $stats['avg_resolution'] ? round($stats['avg_resolution'], 1) . 'j' : '—' }}</div>
                    <div class="stat-label">Rata-rata Resolusi</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
    <!-- Tren Tiket (Line Chart) -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-graph-up text-primary me-2"></i>Tren Tiket — 30 Hari Terakhir</h6>
            </div>
            <div class="card-body">
                <canvas id="trendChart" height="100"></canvas>
            </div>
        </div>
    </div>

    <!-- Kategori (Doughnut) -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-pie-chart text-success me-2"></i>Tiket per Kategori (Bulan Ini)</h6>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                @if($categoryPie->count())
                <canvas id="categoryChart" style="max-height:200px;"></canvas>
                @else
                <p class="text-muted small text-center my-auto">Belum ada tiket bulan ini.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Heatmap Jam Sibuk -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-calendar-heat me-2 text-warning"></i>Heatmap Jam Sibuk — 90 Hari Terakhir</h6>
    </div>
    <div class="card-body">
        @php
            $dayNames = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
        @endphp
        <div style="overflow-x:auto;">
            <table style="border-collapse:separate; border-spacing:2px; font-size:.7rem;">
                <thead>
                    <tr>
                        <th style="width:2.5rem; padding-right:.5rem; font-weight:600; color:#64748b;"></th>
                        @for($h = 0; $h < 24; $h++)
                        <th style="text-align:center; width:2rem; color:#64748b; font-weight:500;">{{ $h }}</th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    @for($d = 0; $d < 7; $d++)
                    <tr>
                        <td style="padding-right:.5rem; font-weight:600; color:#374151; white-space:nowrap;">{{ $dayNames[$d] }}</td>
                        @for($h = 0; $h < 24; $h++)
                        @php
                            $cnt = $chartHeatmap[$d][$h] ?? 0;
                            $intensity = $heatmapMax > 0 ? $cnt / $heatmapMax : 0;
                            $alpha = round($intensity * 0.85 + ($cnt > 0 ? 0.1 : 0), 2);
                            $bg = $cnt > 0 ? "rgba(26,86,219,{$alpha})" : "#f1f5f9";
                            $fg = $intensity > 0.5 ? '#fff' : '#374151';
                        @endphp
                        <td title="{{ $dayNames[$d] }} {{ $h }}:00 — {{ $cnt }} tiket"
                            style="background:{{ $bg }}; color:{{ $fg }}; text-align:center; border-radius:3px;
                                   width:2rem; height:1.6rem; cursor:default; transition:transform .1s;"
                            onmouseover="this.style.transform='scale(1.2)'"
                            onmouseout="this.style.transform='scale(1)'">
                            {{ $cnt ?: '' }}
                        </td>
                        @endfor
                    </tr>
                    @endfor
                </tbody>
            </table>
        </div>
        <div class="d-flex align-items-center gap-2 mt-2">
            <small class="text-muted">Kurang aktif</small>
            @for($i = 1; $i <= 8; $i++)
            <div style="width:1.2rem; height:1rem; background:rgba(26,86,219,{{ round($i/8, 2) }}); border-radius:2px;"></div>
            @endfor
            <small class="text-muted">Paling sibuk</small>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Near SLA -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-alarm text-danger me-2"></i>Tiket Mendekati SLA</h6>
                <a href="{{ route('tickets.index') }}" class="btn btn-sm btn-outline-secondary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                @forelse($nearSla as $ticket)
                    @php
                        $now = now();
                        $pct = $ticket->sla_deadline ? ($now->diffInMinutes($ticket->created_at) / $ticket->sla_deadline->diffInMinutes($ticket->created_at)) * 100 : 0;
                        $slaClass = $pct >= 100 ? 'danger' : ($pct >= 75 ? 'warning' : 'success');
                        $remaining = $ticket->sla_deadline ? now()->diff($ticket->sla_deadline) : null;
                    @endphp
                    <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom hover-bg">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="ticket-number">{{ $ticket->ticket_number }}</span>
                                <span class="badge priority-{{ $ticket->priority }} small">{{ ucfirst($ticket->priority) }}</span>
                            </div>
                            <div class="fw-semibold small text-truncate" style="max-width:300px;">{{ $ticket->title }}</div>
                            <div class="text-muted" style="font-size:.75rem;">
                                <i class="bi bi-person me-1"></i>{{ $ticket->user->name }}
                                @if($ticket->assignee)
                                    · <i class="bi bi-tools me-1"></i>{{ $ticket->assignee->name }}
                                @endif
                            </div>
                        </div>
                        <div class="text-end" style="min-width:120px;">
                            <div class="text-{{ $slaClass }} fw-semibold small">
                                @if($pct >= 100)
                                    <i class="bi bi-x-circle-fill"></i> Terlampaui
                                @elseif($remaining)
                                    <i class="bi bi-clock"></i>
                                    {{ $remaining->h }}j {{ $remaining->i }}m tersisa
                                @endif
                            </div>
                            <div class="progress mt-1" style="height:4px; width:100px; margin-left:auto;">
                                <div class="progress-bar bg-{{ $slaClass }}" style="width:{{ min(100, $pct) }}%"></div>
                            </div>
                        </div>
                        <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                @empty
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-check-circle fs-2 text-success"></i>
                        <p class="mt-2 mb-0">Tidak ada tiket yang mendekati SLA</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Right column -->
    <div class="col-lg-4">
        <!-- Top Teknisi -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-trophy text-warning me-2"></i>Top 5 Teknisi</h6>
            </div>
            <div class="card-body p-0">
                @forelse($topTeknisi as $i => $tek)
                    <div class="d-flex align-items-center gap-3 px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="fw-bold text-muted" style="width:1.5rem; font-size:.8rem;">{{ $i+1 }}</div>
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold"
                             style="width:36px; height:36px; font-size:.8rem; flex-shrink:0;">
                            {{ substr($tek->name, 0, 2) }}
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold small">{{ $tek->name }}</div>
                        </div>
                        <span class="badge bg-success-subtle text-success">{{ $tek->resolved_count }} selesai</span>
                    </div>
                @empty
                    <div class="text-muted small text-center py-3">Belum ada data</div>
                @endforelse
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-activity text-primary me-2"></i>Aktivitas Terbaru</h6>
            </div>
            <div class="card-body p-0">
                @forelse($recentActivity as $log)
                    <div class="px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="d-flex gap-2 align-items-start">
                            <div class="rounded-circle bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0"
                                 style="width:28px; height:28px; font-size:.7rem;">
                                {{ $log->user ? substr($log->user->name, 0, 1) : 'S' }}
                            </div>
                            <div>
                                <div class="small fw-semibold">{{ $log->user?->name ?? 'System' }}</div>
                                <div class="text-muted" style="font-size:.75rem;">{{ str_replace('_', ' ', $log->action) }}</div>
                                <div class="text-muted" style="font-size:.7rem;">{{ $log->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-muted small text-center py-3">Belum ada aktivitas</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "'Segoe UI', sans-serif";
Chart.defaults.color = '#64748b';

// Trend Line Chart
(function() {
    const trend = @json($chartTrend);
    const ctx = document.getElementById('trendChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: trend.labels,
            datasets: [
                {
                    label: 'Tiket Baru',
                    data: trend.new,
                    borderColor: '#1a56db',
                    backgroundColor: 'rgba(26,86,219,.08)',
                    tension: .4,
                    fill: true,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                },
                {
                    label: 'Diselesaikan',
                    data: trend.resolved,
                    borderColor: '#16a34a',
                    backgroundColor: 'rgba(22,163,74,.07)',
                    tension: .4,
                    fill: true,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    borderDash: [4, 2],
                },
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', labels: { boxWidth: 12, padding: 16 } },
                tooltip: { mode: 'index' },
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f1f5f9' } },
                x: { grid: { display: false }, ticks: {
                    maxTicksLimit: 10,
                    maxRotation: 0,
                }},
            }
        }
    });
})();

// Category Doughnut Chart
(function() {
    const pie = @json($categoryPie);
    const ctx = document.getElementById('categoryChart');
    if (!ctx || !pie.length) return;
    const palette = ['#1a56db','#16a34a','#d97706','#dc2626','#7c3aed','#0891b2','#db2777','#65a30d','#ea580c','#0284c7'];
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: pie.map(p => p.label),
            datasets: [{
                data: pie.map(p => p.count),
                backgroundColor: palette.slice(0, pie.length),
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: {
            responsive: true,
            cutout: '60%',
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 10, padding: 10, font: { size: 11 } } },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.label}: ${ctx.raw} tiket`
                    }
                }
            }
        }
    });
})();
</script>
@endpush
