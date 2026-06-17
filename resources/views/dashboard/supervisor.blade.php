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
