@extends('layouts.app')

@section('title', 'Laporan')

@section('breadcrumb')
    <li class="breadcrumb-item active">Laporan</li>
@endsection

@section('content')
<div class="d-flex align-items-start justify-content-between mb-4">
    <div>
        <h1 class="page-title">Laporan & Analitik</h1>
        <p class="page-subtitle">Rekap performa tiket, SLA compliance, dan produktivitas teknisi.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('reports.export', request()->query()) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
        </a>
        <a href="{{ route('reports.export-pdf', ['month' => \Carbon\Carbon::parse($dateFrom)->format('Y-m')]) }}"
           class="btn btn-outline-danger btn-sm" target="_blank">
            <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
        </a>
    </div>
</div>

<!-- Date Filter -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('reports.index') }}" class="row g-2 align-items-end">
            <div class="col-sm-auto">
                <label class="form-label">Dari</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm">
            </div>
            <div class="col-sm-auto">
                <label class="form-label">Sampai</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">Terapkan</button>
            </div>
            <div class="col-auto">
                <a href="{{ route('reports.index', ['date_from' => now()->startOfMonth()->format('Y-m-d'), 'date_to' => now()->format('Y-m-d')]) }}"
                   class="btn btn-outline-secondary btn-sm">Bulan Ini</a>
            </div>
            <div class="col-auto">
                <a href="{{ route('reports.index', ['date_from' => now()->subMonth()->startOfMonth()->format('Y-m-d'), 'date_to' => now()->subMonth()->endOfMonth()->format('Y-m-d')]) }}"
                   class="btn btn-outline-secondary btn-sm">Bulan Lalu</a>
            </div>
            <div class="col-auto ms-auto text-muted small">
                {{ \Carbon\Carbon::parse($dateFrom)->isoFormat('D MMM YYYY') }} – {{ \Carbon\Carbon::parse($dateTo)->isoFormat('D MMM YYYY') }}
            </div>
        </form>
    </div>
</div>

<!-- Summary Stats -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-ticket"></i></div>
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
                <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle"></i></div>
                <div>
                    <div class="stat-value text-success">{{ $stats['resolved'] }}</div>
                    <div class="stat-label">Diselesaikan</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-exclamation-triangle"></i></div>
                <div>
                    <div class="stat-value text-danger">{{ $stats['escalated'] }}</div>
                    <div class="stat-label">Eskalasi SLA</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-clock"></i></div>
                <div>
                    <div class="stat-value">{{ $stats['avg_hours'] ? round($stats['avg_hours'], 1) . 'j' : '—' }}</div>
                    <div class="stat-label">Rata-rata Resolusi</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" id="reportTabs">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabCharts">Grafik</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabBreakdown">Breakdown</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabTeknisi">Performa Teknisi</a></li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tabSla">
            SLA Compliance
            @if($slaCompliance->count())
            @php $avgRate = $slaCompliance->whereNotNull('rate')->avg('rate'); @endphp
            <span class="badge ms-1 {{ $avgRate >= 90 ? 'bg-success' : ($avgRate >= 70 ? 'bg-warning text-dark' : 'bg-danger') }}"
                  style="font-size:.65rem;">{{ round($avgRate) }}%</span>
            @endif
        </a>
    </li>
</ul>

<div class="tab-content">

    <!-- Tab: Grafik -->
    <div class="tab-pane fade show active" id="tabCharts">
        <div class="row g-4 mb-4">
            <!-- Trend Line Chart -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-graph-up text-primary me-2"></i>Tren Tiket per Hari</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="trendChart" height="110"></canvas>
                    </div>
                </div>
            </div>

            <!-- Category Doughnut -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-pie-chart text-success me-2"></i>Per Kategori</h6>
                    </div>
                    <div class="card-body d-flex flex-column align-items-center justify-content-center">
                        @if($categoryPie['data'])
                        <canvas id="categoryChart" style="max-height:220px;"></canvas>
                        @else
                        <p class="text-muted small text-center my-auto">Tidak ada data periode ini.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Priority Bar -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-bar-chart text-warning me-2"></i>Distribusi Prioritas</h6>
            </div>
            <div class="card-body">
                <canvas id="priorityChart" height="60"></canvas>
            </div>
        </div>
    </div>

    <!-- Tab: Breakdown -->
    <div class="tab-pane fade" id="tabBreakdown">
        <div class="row g-4">
            <!-- By Priority -->
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold">Per Prioritas</h6>
                    </div>
                    <div class="card-body">
                        @foreach(['kritis' => ['danger','Kritis'], 'tinggi' => ['warning','Tinggi'], 'sedang' => ['info','Sedang'], 'rendah' => ['secondary','Rendah']] as $p => [$color, $lbl])
                            @php $count = $byPriority[$p] ?? 0; $total = $stats['total'] ?: 1; @endphp
                            <div class="mb-2">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="fw-semibold">{{ $lbl }}</span>
                                    <span class="text-muted">{{ $count }}</span>
                                </div>
                                <div class="progress" style="height:8px;">
                                    <div class="progress-bar bg-{{ $color }}" style="width:{{ ($count/$total)*100 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- By Status -->
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold">Per Status</h6>
                    </div>
                    <div class="card-body">
                        @foreach(\App\Models\Ticket::STATUS_LABELS as $s => $lbl)
                            @php $count = $byStatus[$s] ?? 0; if (!$count) continue; @endphp
                            <div class="d-flex align-items-center justify-content-between py-1 border-bottom">
                                <span class="badge bg-{{ \App\Models\Ticket::STATUS_COLORS[$s] ?? 'secondary' }}-subtle text-{{ \App\Models\Ticket::STATUS_COLORS[$s] ?? 'secondary' }} small">{{ $lbl }}</span>
                                <span class="fw-bold small">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- By Category -->
            <div class="col-xl-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold">Per Kategori</h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Kategori</th><th>Jumlah</th><th>Porsi</th></tr></thead>
                            <tbody>
                                @forelse($byCategory->sortByDesc('count') as $cat)
                                @php $pct = $stats['total'] > 0 ? round(($cat->count/$stats['total'])*100) : 0; @endphp
                                <tr>
                                    <td class="small">{{ $cat->category?->name ?? 'Tanpa Kategori' }}</td>
                                    <td class="small fw-semibold">{{ $cat->count }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height:6px;">
                                                <div class="progress-bar" style="width:{{ $pct }}%"></div>
                                            </div>
                                            <span class="small text-muted" style="width:2.5rem;">{{ $pct }}%</span>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-muted small text-center py-3">Tidak ada data</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Performa Teknisi -->
    <div class="tab-pane fade" id="tabTeknisi">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Teknisi</th>
                            <th>Ditugaskan</th>
                            <th>Selesai</th>
                            <th>% Selesai</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teknisiStats as $tek)
                        @php $pct = $tek->total_assigned > 0 ? round(($tek->resolved_count / $tek->total_assigned) * 100) : 0; @endphp
                        <tr>
                            <td class="small fw-semibold">{{ $tek->name }}</td>
                            <td class="small">{{ $tek->total_assigned }}</td>
                            <td class="small text-success">{{ $tek->resolved_count }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:8px; max-width:120px;">
                                        <div class="progress-bar {{ $pct >= 80 ? 'bg-success' : ($pct >= 50 ? 'bg-warning' : 'bg-danger') }}"
                                             style="width:{{ $pct }}%"></div>
                                    </div>
                                    <span class="small text-muted" style="width:2.5rem;">{{ $pct }}%</span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-3 small">Tidak ada data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab: SLA Compliance -->
    <div class="tab-pane fade" id="tabSla">
        <!-- Month selector -->
        <form method="GET" action="{{ route('reports.index') }}" class="d-flex gap-2 align-items-end mb-4">
            <input type="hidden" name="date_from" value="{{ $dateFrom }}">
            <input type="hidden" name="date_to" value="{{ $dateTo }}">
            <div>
                <label class="form-label">Bulan SLA</label>
                <input type="month" name="sla_month" value="{{ $slaMonth }}" class="form-control form-control-sm">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
            <div class="ms-2 text-muted small mt-auto">
                SLA dihitung dari: tiket yang diselesaikan dalam periode bulan ini
                dengan <code>resolved_at &le; sla_deadline</code>
            </div>
        </form>

        @if($slaCompliance->count())
        @php
            $avgRate = $slaCompliance->whereNotNull('rate')->avg('rate');
            $avgClass = $avgRate >= 90 ? 'success' : ($avgRate >= 70 ? 'warning' : 'danger');
        @endphp

        <!-- Summary badge -->
        <div class="alert alert-{{ $avgClass === 'warning' ? 'warning' : ($avgClass === 'danger' ? 'danger' : 'success') }} d-flex align-items-center gap-3 mb-4">
            <i class="bi bi-shield-check fs-4"></i>
            <div>
                <strong>Rata-rata SLA Compliance: {{ round($avgRate) }}%</strong><br>
                <span class="small">
                    @if($avgRate >= 90) Performa sangat baik. Pertahankan!
                    @elseif($avgRate >= 70) Performa cukup. Ada ruang untuk peningkatan.
                    @else Perlu perhatian serius. Evaluasi beban kerja teknisi dan konfigurasi SLA.
                    @endif
                </span>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Teknisi</th>
                            <th>Total Selesai</th>
                            <th>Tepat Waktu</th>
                            <th>Terlambat</th>
                            <th>Compliance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($slaCompliance->sortByDesc('rate') as $row)
                        @php
                            $rate = $row['rate'] ?? 0;
                            $barColor = $rate >= 90 ? 'bg-success' : ($rate >= 70 ? 'bg-warning' : 'bg-danger');
                        @endphp
                        <tr>
                            <td class="small fw-semibold">{{ $row['name'] }}</td>
                            <td class="small">{{ $row['total'] }}</td>
                            <td class="small text-success fw-semibold">{{ $row['on_time'] }}</td>
                            <td class="small {{ $row['late'] > 0 ? 'text-danger' : 'text-muted' }}">{{ $row['late'] }}</td>
                            <td>
                                @if($row['rate'] !== null)
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:8px; max-width:120px;">
                                        <div class="progress-bar {{ $barColor }}" style="width:{{ $rate }}%"></div>
                                    </div>
                                    <span class="small fw-semibold {{ $rate >= 90 ? 'text-success' : ($rate >= 70 ? 'text-warning' : 'text-danger') }}">
                                        {{ $rate }}%
                                    </span>
                                </div>
                                @else
                                <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SLA Compliance Bar Chart -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-bar-chart-line text-primary me-2"></i>SLA Compliance per Teknisi</h6>
            </div>
            <div class="card-body">
                <canvas id="slaChart" height="80"></canvas>
            </div>
        </div>
        @else
        <div class="text-center py-5 text-muted">
            <i class="bi bi-clipboard-data fs-2"></i>
            <p class="mt-2 mb-0">Tidak ada data resolusi pada bulan {{ \Carbon\Carbon::createFromFormat('Y-m', $slaMonth)->isoFormat('MMMM YYYY') }}.</p>
        </div>
        @endif
    </div>

</div><!-- /tab-content -->
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
                    backgroundColor: 'rgba(26,86,219,.09)',
                    tension: .4, fill: true, pointRadius: 2, pointHoverRadius: 5,
                },
                {
                    label: 'Diselesaikan',
                    data: trend.resolved,
                    borderColor: '#16a34a',
                    backgroundColor: 'rgba(22,163,74,.07)',
                    tension: .4, fill: true, pointRadius: 2, pointHoverRadius: 5,
                    borderDash: [4,2],
                },
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'top', labels: { boxWidth: 12 } } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f1f5f9' } },
                x: { grid: { display: false }, ticks: { maxTicksLimit: 12, maxRotation: 0 } },
            }
        }
    });
})();

// Category Doughnut
(function() {
    const pie = @json($categoryPie);
    const ctx = document.getElementById('categoryChart');
    if (!ctx || !pie.data.length) return;
    const palette = ['#1a56db','#16a34a','#d97706','#dc2626','#7c3aed','#0891b2','#db2777','#65a30d'];
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: pie.labels,
            datasets: [{ data: pie.data, backgroundColor: palette.slice(0, pie.data.length), borderWidth: 2, borderColor: '#fff' }]
        },
        options: {
            responsive: true, cutout: '60%',
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 10, padding: 10, font: { size: 11 } } },
                tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.raw} tiket` } }
            }
        }
    });
})();

// Priority Bar Chart
(function() {
    const byPriority = @json($byPriority);
    const ctx = document.getElementById('priorityChart');
    if (!ctx) return;
    const labels = ['Kritis', 'Tinggi', 'Sedang', 'Rendah'];
    const keys   = ['kritis', 'tinggi', 'sedang', 'rendah'];
    const colors = ['#dc2626', '#d97706', '#1a56db', '#64748b'];
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Jumlah Tiket',
                data: keys.map(k => byPriority[k] || 0),
                backgroundColor: colors,
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true, indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f1f5f9' } },
                y: { grid: { display: false } },
            }
        }
    });
})();

// SLA Compliance Bar Chart
(function() {
    const sla = @json($slaCompliance);
    const ctx = document.getElementById('slaChart');
    if (!ctx || !sla.length) return;
    const sorted = [...sla].sort((a, b) => (b.rate || 0) - (a.rate || 0));
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: sorted.map(r => r.name),
            datasets: [
                {
                    label: 'Tepat Waktu',
                    data: sorted.map(r => r.on_time),
                    backgroundColor: '#16a34a',
                    borderRadius: 4,
                    stack: 'sla',
                },
                {
                    label: 'Terlambat',
                    data: sorted.map(r => r.late),
                    backgroundColor: '#dc2626',
                    borderRadius: 4,
                    stack: 'sla',
                },
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top', labels: { boxWidth: 12 } },
                tooltip: {
                    callbacks: {
                        afterBody: (items) => {
                            const idx = items[0].dataIndex;
                            const r = sorted[idx];
                            return r.rate !== null ? [`Compliance: ${r.rate}%`] : [];
                        }
                    }
                }
            },
            scales: {
                x: { stacked: true, grid: { display: false } },
                y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f1f5f9' } },
            }
        }
    });
})();
</script>
@endpush
