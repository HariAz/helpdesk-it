@extends('layouts.app')

@section('title', 'Log Aktivitas')

@section('breadcrumb')
    <li class="breadcrumb-item active">Log Aktivitas</li>
@endsection

@section('content')
<div class="d-flex align-items-start justify-content-between mb-4">
    <div>
        <h1 class="page-title">Log Aktivitas</h1>
        <p class="page-subtitle">Audit trail seluruh aktivitas pengguna dalam sistem.</p>
    </div>
    <a href="{{ route('activity-logs.export', request()->query()) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-download me-1"></i> Export CSV
    </a>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('activity-logs.index') }}" class="row g-2 align-items-end">
            <div class="col-sm-4">
                <label class="form-label">Cari</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm"
                       placeholder="Nama user, aksi...">
            </div>
            <div class="col-sm-3">
                <label class="form-label">Jenis Aksi</label>
                <select name="action" class="form-select form-select-sm">
                    <option value="">Semua Aksi</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>
                            {{ str_replace('_', ' ', ucfirst($action)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label">Dari</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
            </div>
            <div class="col-sm-2">
                <label class="form-label">Sampai</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
                <a href="{{ route('activity-logs.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Pengguna</th>
                        <th>Aksi</th>
                        <th>IP Address</th>
                        <th>User Agent</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="small text-muted" style="white-space:nowrap;">
                                {{ $log->created_at->isoFormat('D MMM HH:mm:ss') }}
                            </td>
                            <td>
                                @if($log->user)
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold"
                                             style="width:28px; height:28px; font-size:.7rem; flex-shrink:0;">
                                            {{ $log->user->initials }}
                                        </div>
                                        <div>
                                            <div class="small fw-semibold">{{ $log->user->name }}</div>
                                            <div class="text-muted" style="font-size:.72rem;">{{ ucfirst($log->user->role) }}</div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted small">System</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $actionColor = match(true) {
                                        str_contains($log->action, 'login') => 'success',
                                        str_contains($log->action, 'logout') => 'secondary',
                                        str_contains($log->action, 'delete') || str_contains($log->action, 'cancel') => 'danger',
                                        str_contains($log->action, 'create') || str_contains($log->action, 'added') => 'primary',
                                        default => 'info',
                                    };
                                @endphp
                                <span class="badge bg-{{ $actionColor }}-subtle text-{{ $actionColor }} small">
                                    {{ str_replace('_', ' ', $log->action) }}
                                </span>
                                @if($log->properties)
                                    <div class="text-muted" style="font-size:.72rem; margin-top:.2rem;">
                                        @foreach($log->properties as $k => $v)
                                            <span>{{ $k }}: {{ is_array($v) ? json_encode($v) : $v }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $log->ip_address }}</td>
                            <td>
                                @if($log->user_agent)
                                    <button class="btn btn-link btn-sm p-0 text-muted" style="font-size:.75rem;"
                                            onclick="this.nextElementSibling.classList.toggle('d-none')">
                                        <i class="bi bi-chevron-down"></i>
                                        {{ Str::limit($log->user_agent, 30) }}
                                    </button>
                                    <div class="d-none" style="font-size:.72rem; color:#94a3b8; margin-top:.2rem; word-break:break-all;">
                                        {{ $log->user_agent }}
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-clock-history fs-2"></i>
                                <p class="mt-2 mb-0">Tidak ada log aktivitas</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-top d-flex align-items-center justify-content-between">
            <div class="text-muted small">Total: {{ $logs->total() }} entri</div>
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
