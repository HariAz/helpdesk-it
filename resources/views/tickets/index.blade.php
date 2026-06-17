@extends('layouts.app')

@section('title', 'Daftar Tiket')

@section('breadcrumb')
    <li class="breadcrumb-item active">Tiket</li>
@endsection

@section('content')
<div class="d-flex align-items-start justify-content-between mb-4">
    <div>
        <h1 class="page-title">Daftar Tiket</h1>
        <p class="page-subtitle">Kelola dan pantau semua permintaan layanan IT.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('tickets.export', request()->query()) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-download me-1"></i> Export CSV
        </a>
        @if(auth()->user()->isUser() || auth()->user()->isTeknisi())
            <a href="{{ route('tickets.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Buat Tiket
            </a>
        @endif
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4 border-0 gap-1">
    @foreach(['all' => 'Semua', 'active' => 'Aktif', 'pending' => 'Pending', 'resolved' => 'Selesai', 'closed' => 'Ditutup'] as $tab => $label)
        <li class="nav-item">
            <a class="nav-link px-3 py-2 {{ request('tab', 'all') === $tab ? 'active bg-primary text-white border-primary' : 'bg-white border text-secondary' }}"
               style="border-radius:8px; font-size:.85rem;"
               href="{{ route('tickets.index', array_merge(request()->query(), ['tab' => $tab])) }}">
                {{ $label }}
            </a>
        </li>
    @endforeach
</ul>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('tickets.index') }}" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="{{ request('tab', 'all') }}">
            <div class="col-sm-6 col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    @foreach(\App\Models\Ticket::STATUS_LABELS as $val => $lbl)
                        <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-md-2">
                <label class="form-label">Prioritas</label>
                <select name="priority" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="kritis" {{ request('priority') === 'kritis' ? 'selected' : '' }}>Kritis</option>
                    <option value="tinggi" {{ request('priority') === 'tinggi' ? 'selected' : '' }}>Tinggi</option>
                    <option value="sedang" {{ request('priority') === 'sedang' ? 'selected' : '' }}>Sedang</option>
                    <option value="rendah" {{ request('priority') === 'rendah' ? 'selected' : '' }}>Rendah</option>
                </select>
            </div>
            <div class="col-sm-6 col-md-3">
                <label class="form-label">Kategori</label>
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            @if(auth()->user()->isSupervisor())
            <div class="col-sm-6 col-md-3">
                <label class="form-label">Teknisi</label>
                <select name="assigned_to" class="form-select form-select-sm">
                    <option value="">Semua Teknisi</option>
                    @foreach($teknisi as $tek)
                        <option value="{{ $tek->id }}" {{ request('assigned_to') == $tek->id ? 'selected' : '' }}>{{ $tek->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-sm-6 col-md-2">
                <label class="form-label">Dari</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
            </div>
            <div class="col-sm-6 col-md-2">
                <label class="form-label">Sampai</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i> Filter</button>
                <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($tickets->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="mt-2 text-muted">Tidak ada tiket ditemukan</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>
                                <a href="{{ route('tickets.index', array_merge(request()->query(), ['sort' => 'ticket_number', 'dir' => request('dir') === 'asc' ? 'desc' : 'asc'])) }}"
                                   class="text-decoration-none text-muted d-flex align-items-center gap-1">
                                    No. Tiket
                                    @if(request('sort') === 'ticket_number')
                                        <i class="bi bi-arrow-{{ request('dir') === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </a>
                            </th>
                            <th>Judul</th>
                            <th>
                                <a href="{{ route('tickets.index', array_merge(request()->query(), ['sort' => 'priority', 'dir' => request('dir') === 'asc' ? 'desc' : 'asc'])) }}"
                                   class="text-decoration-none text-muted">Prioritas</a>
                            </th>
                            <th>Status</th>
                            <th>Pelapor</th>
                            <th>Teknisi</th>
                            <th>
                                <a href="{{ route('tickets.index', array_merge(request()->query(), ['sort' => 'sla_deadline', 'dir' => request('dir') === 'asc' ? 'desc' : 'asc'])) }}"
                                   class="text-decoration-none text-muted">SLA</a>
                            </th>
                            <th>
                                <a href="{{ route('tickets.index', array_merge(request()->query(), ['sort' => 'created_at', 'dir' => request('dir') === 'asc' ? 'desc' : 'asc'])) }}"
                                   class="text-decoration-none text-muted">Dibuat</a>
                            </th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tickets as $ticket)
                            @php
                                $slaStatus = 'ok';
                                if ($ticket->sla_deadline && !in_array($ticket->status, ['resolved','closed','cancelled'])) {
                                    $pct = (now()->diffInMinutes($ticket->created_at) / max(1, $ticket->sla_deadline->diffInMinutes($ticket->created_at))) * 100;
                                    if ($pct >= 100) $slaStatus = 'overdue';
                                    elseif ($pct >= 75) $slaStatus = 'warning';
                                }
                            @endphp
                            <tr class="{{ $ticket->is_escalated ? 'table-danger' : '' }}">
                                <td>
                                    <span class="ticket-number">{{ $ticket->ticket_number }}</span>
                                    @if($ticket->is_escalated)
                                        <i class="bi bi-exclamation-triangle-fill text-danger ms-1" title="Eskalasi"></i>
                                    @endif
                                </td>
                                <td style="max-width:250px;">
                                    <div class="fw-semibold small text-truncate">{{ $ticket->title }}</div>
                                    <div class="text-muted" style="font-size:.72rem;">{{ $ticket->category?->name }}</div>
                                </td>
                                <td>
                                    <span class="badge priority-{{ $ticket->priority }} px-2 py-1 small">{{ ucfirst($ticket->priority) }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ \App\Models\Ticket::STATUS_COLORS[$ticket->status] ?? 'secondary' }}-subtle text-{{ \App\Models\Ticket::STATUS_COLORS[$ticket->status] ?? 'secondary' }} small">
                                        {{ \App\Models\Ticket::STATUS_LABELS[$ticket->status] ?? $ticket->status }}
                                    </span>
                                </td>
                                <td class="small">{{ $ticket->user->name }}</td>
                                <td class="small">{{ $ticket->assignee?->name ?? '<span class="text-muted">—</span>' }}</td>
                                <td>
                                    @if($ticket->sla_deadline && !in_array($ticket->status, ['resolved','closed','cancelled']))
                                        <span class="sla-{{ $slaStatus }}" style="font-size:.78rem;">
                                            <span class="sla-dot sla-dot-{{ $slaStatus }}"></span>
                                            @if($slaStatus === 'overdue')
                                                Terlampaui
                                            @else
                                                {{ $ticket->sla_deadline->format('d/m H:i') }}
                                            @endif
                                        </span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $ticket->created_at->format('d/m/y H:i') }}</td>
                                <td>
                                    <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-top d-flex align-items-center justify-content-between">
                <div class="text-muted small">
                    Menampilkan {{ $tickets->firstItem() }}–{{ $tickets->lastItem() }} dari {{ $tickets->total() }} tiket
                </div>
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
