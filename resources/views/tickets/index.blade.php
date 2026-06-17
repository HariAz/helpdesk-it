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
                <button type="button" class="btn btn-outline-success btn-sm" onclick="saveCurrentFilter()" title="Simpan filter ini">
                    <i class="bi bi-bookmark-plus"></i>
                </button>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="savedFiltersBtn" data-bs-toggle="dropdown">
                        <i class="bi bi-bookmark"></i>
                    </button>
                    <ul class="dropdown-menu" id="savedFiltersList" style="min-width:220px;">
                        <li><span class="dropdown-item text-muted small">Memuat...</span></li>
                    </ul>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Bulk Action Toolbar (hidden until rows selected) -->
<div id="bulkToolbar" class="card border-0 shadow-sm mb-3 border-primary" style="display:none; border-left:4px solid #1a56db !important;">
    <div class="card-body py-2 px-3 d-flex align-items-center gap-3 flex-wrap">
        <span class="small fw-semibold text-primary"><i class="bi bi-check-square me-1"></i><span id="bulkCount">0</span> tiket dipilih</span>
        <form id="bulkForm" method="POST" action="{{ route('tickets.bulk-action') }}">
            @csrf
            <input type="hidden" name="action" id="bulkAction">
            <div id="bulkTicketIds"></div>
            @if(auth()->user()->isSupervisor())
            <select name="assigned_to" id="bulkAssignTo" class="form-select form-select-sm d-none" style="width:auto;">
                <option value="">— Pilih teknisi —</option>
                @foreach($teknisi as $tek)
                <option value="{{ $tek->id }}">{{ $tek->name }}</option>
                @endforeach
            </select>
            @endif
        </form>
        @if(auth()->user()->isSupervisor())
        <button class="btn btn-sm btn-outline-primary" onclick="triggerBulk('assign')">
            <i class="bi bi-person-check me-1"></i> Assign
        </button>
        <button class="btn btn-sm btn-outline-success" onclick="triggerBulk('close')">
            <i class="bi bi-check-circle me-1"></i> Tutup
        </button>
        <button class="btn btn-sm btn-outline-danger" onclick="triggerBulk('cancel')">
            <i class="bi bi-x-circle me-1"></i> Batalkan
        </button>
        @endif
        <button class="btn btn-sm btn-outline-secondary" onclick="triggerBulk('export')">
            <i class="bi bi-download me-1"></i> Export
        </button>
        <button class="btn btn-sm btn-link text-muted" onclick="clearSelection()">Batalkan Pilihan</button>
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
                            <th style="width:2.5rem;">
                                <input type="checkbox" class="form-check-input" id="selectAll" title="Pilih semua">
                            </th>
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
                                    <input type="checkbox" class="form-check-input ticket-cb" value="{{ $ticket->id }}" data-id="{{ $ticket->id }}">
                                </td>
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

@push('scripts')
<script>
// ── Bulk Actions ──────────────────────────────────────────────────
const selectAll = document.getElementById('selectAll');
const toolbar   = document.getElementById('bulkToolbar');
const bulkCount = document.getElementById('bulkCount');
const bulkIds   = document.getElementById('bulkTicketIds');
const assignSel = document.getElementById('bulkAssignTo');

function getChecked() { return [...document.querySelectorAll('.ticket-cb:checked')]; }

function updateToolbar() {
    const checked = getChecked();
    toolbar.style.display = checked.length ? 'block' : 'none';
    bulkCount.textContent = checked.length;
    bulkIds.innerHTML = checked.map(cb => `<input type="hidden" name="ticket_ids[]" value="${cb.value}">`).join('');
}

selectAll?.addEventListener('change', function() {
    document.querySelectorAll('.ticket-cb').forEach(cb => cb.checked = this.checked);
    updateToolbar();
});

document.querySelectorAll('.ticket-cb').forEach(cb => cb.addEventListener('change', () => {
    const all = document.querySelectorAll('.ticket-cb');
    const checked = getChecked();
    selectAll.checked = checked.length === all.length;
    selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
    updateToolbar();
}));

function clearSelection() {
    document.querySelectorAll('.ticket-cb').forEach(cb => cb.checked = false);
    if (selectAll) { selectAll.checked = false; selectAll.indeterminate = false; }
    updateToolbar();
}

function triggerBulk(action) {
    if (!getChecked().length) return;
    if (action === 'assign') {
        if (!assignSel) return;
        assignSel.classList.remove('d-none');
        if (!assignSel.value) { assignSel.focus(); return; }
    }
    if ((action === 'close' || action === 'cancel') && !confirm(`Terapkan aksi "${action}" ke ${getChecked().length} tiket yang dipilih?`)) return;
    document.getElementById('bulkAction').value = action;
    document.getElementById('bulkForm').submit();
}

// ── Saved Filters ─────────────────────────────────────────────────
const savedFiltersBtn  = document.getElementById('savedFiltersBtn');
const savedFiltersList = document.getElementById('savedFiltersList');

function loadSavedFilters() {
    fetch('/saved-filters').then(r => r.json()).then(filters => {
        if (!filters.length) {
            savedFiltersList.innerHTML = '<li><span class="dropdown-item text-muted small">Belum ada filter tersimpan.</span></li>';
            return;
        }
        savedFiltersList.innerHTML = filters.map(f =>
            `<li class="d-flex align-items-center px-2">
                <a class="dropdown-item flex-grow-1 small py-1" href="#" onclick="applyFilter(${JSON.stringify(f.filters)}); return false;">
                    <i class="bi bi-bookmark-fill me-1 text-primary"></i>${f.name}
                </a>
                <button class="btn btn-link btn-sm p-0 text-danger" onclick="deleteFilter(${f.id})" title="Hapus">
                    <i class="bi bi-trash" style="font-size:.75rem;"></i>
                </button>
            </li>`
        ).join('') + '<li><hr class="dropdown-divider"><li><span class="dropdown-item text-muted" style="font-size:.72rem;">Klik nama filter untuk menerapkan</span></li>';
    });
}

savedFiltersBtn?.addEventListener('show.bs.dropdown', loadSavedFilters);

function applyFilter(filters) {
    window.location.href = '/tickets?' + new URLSearchParams(filters).toString();
}

function saveCurrentFilter() {
    const name = prompt('Nama filter ini:');
    if (!name) return;
    const params = Object.fromEntries(new URLSearchParams(window.location.search).entries());
    delete params.page;
    fetch('/saved-filters', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: JSON.stringify({ name, filters: params })
    }).then(r => r.json()).then(d => { if (d.message) alert(d.message); });
}

function deleteFilter(id) {
    if (!confirm('Hapus filter ini?')) return;
    fetch(`/saved-filters/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
    }).then(() => loadSavedFilters());
}
</script>
@endpush
