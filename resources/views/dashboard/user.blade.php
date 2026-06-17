@extends('layouts.app')

@section('title', 'Dashboard')

@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
<div class="d-flex align-items-start justify-content-between mb-4">
    <div>
        <h1 class="page-title">Tiket Saya</h1>
        <p class="page-subtitle">Pantau status permintaan layanan IT Anda.</p>
    </div>
    <a href="{{ route('tickets.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Buat Tiket Baru
    </a>
</div>

<!-- Active Tickets -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-hourglass-split text-warning me-2"></i>Tiket Aktif</h6>
    </div>
    @if($activeTickets->isEmpty())
        <div class="card-body text-center py-5">
            <i class="bi bi-check-circle-fill fs-2 text-success"></i>
            <p class="mt-2 mb-0 text-muted">Tidak ada tiket aktif saat ini</p>
            <a href="{{ route('tickets.create') }}" class="btn btn-primary btn-sm mt-3">
                <i class="bi bi-plus me-1"></i> Buat Tiket Baru
            </a>
        </div>
    @else
        <div class="list-group list-group-flush">
            @foreach($activeTickets as $ticket)
                @php
                    $slaStatus = 'ok';
                    if ($ticket->sla_deadline) {
                        $pct = (now()->diffInMinutes($ticket->created_at) / max(1, $ticket->sla_deadline->diffInMinutes($ticket->created_at))) * 100;
                        if ($pct >= 100) $slaStatus = 'overdue';
                        elseif ($pct >= 75) $slaStatus = 'warning';
                    }
                @endphp
                <a href="{{ route('tickets.show', $ticket) }}" class="list-group-item list-group-item-action px-4 py-3">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="ticket-number">{{ $ticket->ticket_number }}</span>
                                <span class="badge priority-{{ $ticket->priority }} small">{{ ucfirst($ticket->priority) }}</span>
                                @if($ticket->status === 'pending_user')
                                    <span class="badge bg-warning text-dark small">Butuh Balasan Anda</span>
                                @endif
                            </div>
                            <div class="fw-semibold">{{ $ticket->title }}</div>
                            <div class="text-muted small">
                                {{ $ticket->category?->name }}
                                @if($ticket->assignee)
                                    · Ditangani: {{ $ticket->assignee->name }}
                                @else
                                    · <span class="text-secondary">Belum di-assign</span>
                                @endif
                            </div>
                        </div>
                        <div class="text-end flex-shrink-0">
                            <span class="badge bg-{{ \App\Models\Ticket::STATUS_COLORS[$ticket->status] ?? 'secondary' }}-subtle text-{{ \App\Models\Ticket::STATUS_COLORS[$ticket->status] ?? 'secondary' }} mb-1">
                                {{ \App\Models\Ticket::STATUS_LABELS[$ticket->status] ?? $ticket->status }}
                            </span>
                            @if($ticket->sla_deadline)
                                <div class="sla-{{ $slaStatus }} mt-1" style="font-size:.75rem;">
                                    <span class="sla-dot sla-dot-{{ $slaStatus }}"></span>
                                    @if($slaStatus === 'overdue')
                                        SLA Terlampaui
                                    @else
                                        Deadline: {{ $ticket->sla_deadline->format('d M H:i') }}
                                    @endif
                                </div>
                            @endif
                            <div class="text-muted" style="font-size:.72rem;">{{ $ticket->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>

<!-- Closed Tickets -->
@if($closedTickets->isNotEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-archive text-secondary me-2"></i>Riwayat Tiket Selesai</h6>
    </div>
    <div class="list-group list-group-flush">
        @foreach($closedTickets as $ticket)
            <a href="{{ route('tickets.show', $ticket) }}" class="list-group-item list-group-item-action px-4 py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="ticket-number">{{ $ticket->ticket_number }}</span>
                        </div>
                        <div class="fw-semibold small">{{ $ticket->title }}</div>
                        <div class="text-muted" style="font-size:.75rem;">
                            Ditutup {{ $ticket->closed_at?->format('d M Y') ?? $ticket->updated_at->format('d M Y') }}
                        </div>
                    </div>
                    @if($ticket->rating)
                        <div class="text-warning">
                            @for($s=1; $s<=5; $s++)
                                <i class="bi bi-star{{ $s <= $ticket->rating->rating ? '-fill' : '' }}" style="font-size:.8rem;"></i>
                            @endfor
                        </div>
                    @endif
                    <span class="badge bg-dark-subtle text-dark">
                        {{ \App\Models\Ticket::STATUS_LABELS[$ticket->status] ?? $ticket->status }}
                    </span>
                </div>
            </a>
        @endforeach
    </div>
    <div class="card-footer bg-white text-end">
        <a href="{{ route('tickets.index', ['tab' => 'closed']) }}" class="btn btn-sm btn-outline-secondary">
            Lihat Semua Riwayat
        </a>
    </div>
</div>
@endif
@endsection
