@extends('layouts.app')

@section('title', 'Dashboard Teknisi')

@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
<div class="mb-4">
    <h1 class="page-title">Dashboard Teknisi</h1>
    <p class="page-subtitle">Selamat datang, {{ auth()->user()->name }}. Berikut tiket yang perlu ditangani.</p>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-ticket-detailed"></i></div>
                <div>
                    <div class="stat-value">{{ $stats['active'] }}</div>
                    <div class="stat-label">Tiket Aktif Saya</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle"></i></div>
                <div>
                    <div class="stat-value">{{ $stats['done_today'] }}</div>
                    <div class="stat-label">Selesai Hari Ini</div>
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
                    <div class="stat-label">SLA Terlampaui</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-person-raised-hand"></i></div>
                <div>
                    <div class="stat-value">{{ $stats['pending_user'] }}</div>
                    <div class="stat-label">Pending User</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts row -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-graph-up me-2 text-primary"></i>Progress Harian (14 Hari Terakhir)</h6>
            </div>
            <div class="card-body">
                <canvas id="progressChart" height="90"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-pie-chart me-2 text-warning"></i>Tiket Aktif per Prioritas</h6>
            </div>
            <div class="card-body d-flex flex-column justify-content-center">
                @php
                    $priorityColors = ['kritis' => '#dc2626','tinggi' => '#d97706','sedang' => '#2563eb','rendah' => '#64748b'];
                    $priorityLabels = ['kritis' => 'Kritis','tinggi' => 'Tinggi','sedang' => 'Sedang','rendah' => 'Rendah'];
                @endphp
                @if($workloadPriority->sum() > 0)
                    <canvas id="priorityChart" height="160"></canvas>
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-check-circle fs-2 text-success"></i>
                        <p class="mt-2 mb-0 small">Semua tiket selesai!</p>
                    </div>
                @endif
                <div class="mt-3">
                    @foreach(['kritis','tinggi','sedang','rendah'] as $p)
                        @if(($workloadPriority[$p] ?? 0) > 0)
                        <div class="d-flex justify-content-between align-items-center small py-1">
                            <span><span class="badge priority-{{ $p }} px-2">{{ $priorityLabels[$p] }}</span></span>
                            <span class="fw-bold">{{ $workloadPriority[$p] ?? 0 }}</span>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- My Tickets -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-list-check me-2"></i>Tiket yang Di-assign ke Saya</h6>
        <a href="{{ route('tickets.index') }}" class="btn btn-sm btn-outline-secondary">Lihat Semua</a>
    </div>
    @if($myTickets->isEmpty())
        <div class="card-body text-center py-5">
            <i class="bi bi-inbox fs-2 text-muted"></i>
            <p class="mt-2 mb-0 text-muted">Tidak ada tiket aktif yang di-assign ke Anda</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>No. Tiket</th>
                        <th>Judul</th>
                        <th>Prioritas</th>
                        <th>Status</th>
                        <th>Pelapor</th>
                        <th>SLA</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($myTickets as $ticket)
                        @php
                            $slaStatus = 'ok';
                            if ($ticket->sla_deadline) {
                                $pct = (now()->diffInMinutes($ticket->created_at) / max(1, $ticket->sla_deadline->diffInMinutes($ticket->created_at))) * 100;
                                if ($pct >= 100) $slaStatus = 'overdue';
                                elseif ($pct >= 75) $slaStatus = 'warning';
                            }
                        @endphp
                        <tr>
                            <td><span class="ticket-number">{{ $ticket->ticket_number }}</span></td>
                            <td>
                                <div class="fw-semibold small">{{ Str::limit($ticket->title, 50) }}</div>
                                <div class="text-muted" style="font-size:.75rem;">{{ $ticket->category?->name }}</div>
                            </td>
                            <td>
                                <span class="badge priority-{{ $ticket->priority }} px-2 py-1">
                                    {{ ucfirst($ticket->priority) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ \App\Models\Ticket::STATUS_COLORS[$ticket->status] ?? 'secondary' }}-subtle text-{{ \App\Models\Ticket::STATUS_COLORS[$ticket->status] ?? 'secondary' }}">
                                    {{ \App\Models\Ticket::STATUS_LABELS[$ticket->status] ?? $ticket->status }}
                                </span>
                            </td>
                            <td class="small">{{ $ticket->user->name }}</td>
                            <td>
                                @if($ticket->sla_deadline)
                                    <span class="sla-{{ $slaStatus }}">
                                        <span class="sla-dot sla-dot-{{ $slaStatus }}"></span>
                                        @if($slaStatus === 'overdue')
                                            Terlampaui
                                        @else
                                            {{ $ticket->sla_deadline->format('d/m H:i') }}
                                        @endif
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
const progressCtx = document.getElementById('progressChart');
if (progressCtx) {
    new Chart(progressCtx, {
        type: 'bar',
        data: {
            labels: @json($chartProgress['labels']),
            datasets: [{
                label: 'Tiket Diselesaikan',
                data: @json($chartProgress['data']),
                backgroundColor: 'rgba(26,86,219,.75)',
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(0,0,0,.06)' } },
                x: { grid: { display: false } }
            }
        }
    });
}

const priorityCtx = document.getElementById('priorityChart');
if (priorityCtx) {
    const data = @json($workloadPriority);
    new Chart(priorityCtx, {
        type: 'doughnut',
        data: {
            labels: ['Kritis','Tinggi','Sedang','Rendah'],
            datasets: [{
                data: [data.kritis||0, data.tinggi||0, data.sedang||0, data.rendah||0],
                backgroundColor: ['#dc2626','#d97706','#2563eb','#64748b'],
                borderWidth: 2,
            }]
        },
        options: {
            cutout: '65%',
            plugins: { legend: { display: false } }
        }
    });
}
</script>
@endpush
