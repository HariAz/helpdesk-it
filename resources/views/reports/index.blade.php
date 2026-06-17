@extends('layouts.app')

@section('title', 'Laporan')

@section('breadcrumb')
    <li class="breadcrumb-item active">Laporan</li>
@endsection

@section('content')
<div class="d-flex align-items-start justify-content-between mb-4">
    <div>
        <h1 class="page-title">Laporan & Statistik</h1>
        <p class="page-subtitle">Rekap performa tiket dan produktivitas teknisi.</p>
    </div>
    <a href="{{ route('reports.export', request()->query()) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-download me-1"></i> Export CSV
    </a>
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
            <div class="col-auto ms-auto text-muted small">
                Periode: {{ \Carbon\Carbon::parse($dateFrom)->isoFormat('D MMM YYYY') }} – {{ \Carbon\Carbon::parse($dateTo)->isoFormat('D MMM YYYY') }}
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

<div class="row g-4">
    <!-- By Priority -->
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold">Per Prioritas</h6>
            </div>
            <div class="card-body">
                @foreach(['kritis' => ['danger', 'Kritis'], 'tinggi' => ['warning', 'Tinggi'], 'sedang' => ['info', 'Sedang'], 'rendah' => ['secondary', 'Rendah']] as $p => [$color, $lbl])
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

    <!-- Teknisi Performance -->
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold">Performa Teknisi</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
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
                                        <div class="progress flex-grow-1" style="height:6px;">
                                            <div class="progress-bar bg-success" style="width:{{ $pct }}%"></div>
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
</div>
@endsection
