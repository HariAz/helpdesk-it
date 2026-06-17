@extends('layouts.app')
@section('title', 'Webhook')
@section('breadcrumb')
    <li class="breadcrumb-item active">Webhook</li>
@endsection
@section('content')
<div class="d-flex align-items-start justify-content-between mb-4">
    <div>
        <h1 class="page-title">Webhook Endpoints</h1>
        <p class="page-subtitle">Kirim payload ke sistem eksternal saat event tiket terjadi.</p>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-circle me-1"></i> Tambah Endpoint
    </button>
</div>

@if($endpoints->isEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-broadcast fs-2"></i>
        <p class="mt-2 mb-0">Belum ada webhook. Tambahkan endpoint untuk mulai menerima notifikasi.</p>
    </div>
</div>
@else
<div class="row g-3">
@foreach($endpoints as $ep)
<div class="col-12">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex align-items-start gap-3">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="fw-bold">{{ $ep->name }}</span>
                        @if($ep->is_active)
                        <span class="badge bg-success-subtle text-success small">Aktif</span>
                        @else
                        <span class="badge bg-secondary-subtle text-secondary small">Nonaktif</span>
                        @endif
                    </div>
                    <div class="font-monospace small text-muted mb-2">{{ $ep->url }}</div>
                    <div class="d-flex flex-wrap gap-1 mb-2">
                        @foreach($ep->events ?? [] as $event)
                        <span class="badge bg-primary-subtle text-primary" style="font-size:.65rem;">{{ $event }}</span>
                        @endforeach
                    </div>
                    <div class="text-muted" style="font-size:.72rem;">
                        Terakhir dipicu: {{ $ep->last_triggered_at ? $ep->last_triggered_at->diffForHumans() : 'Belum pernah' }}
                        &nbsp;·&nbsp; Total pengiriman: {{ $ep->logs_count }}
                    </div>
                    <div class="mt-2">
                        <span class="small text-muted">Secret: </span>
                        <code class="small" id="secret_{{ $ep->id }}">{{ str_repeat('•', 20) }}</code>
                        <button class="btn btn-link btn-sm p-0 ms-1" style="font-size:.7rem;"
                                onclick="toggleSecret({{ $ep->id }}, '{{ $ep->secret }}')">tampilkan</button>
                    </div>
                </div>
                <div class="d-flex gap-1 flex-shrink-0">
                    <button class="btn btn-sm btn-outline-secondary" title="Test"
                            onclick="testWebhook({{ $ep->id }}, this)">
                        <i class="bi bi-send"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-info" title="Log"
                            onclick="showLogs({{ $ep->id }})">
                        <i class="bi bi-journal-text"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-warning" title="Edit"
                            data-bs-toggle="modal" data-bs-target="#editModal{{ $ep->id }}">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <form method="POST" action="{{ route('settings.webhooks.destroy', $ep) }}" class="d-inline">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" title="Hapus"
                                onclick="return confirm('Hapus webhook \'{{ $ep->name }}\'?')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal{{ $ep->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('settings.webhooks.update', $ep) }}" class="modal-content">
            @csrf @method('PATCH')
            <div class="modal-header">
                <h5 class="modal-title">Edit Webhook</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" class="form-control" value="{{ $ep->name }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">URL</label>
                    <input type="url" name="url" class="form-control" value="{{ $ep->url }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Events</label>
                    <div class="row g-2">
                        @foreach(\App\Models\WebhookEndpoint::EVENTS as $ev => $label)
                        <div class="col-sm-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="events[]"
                                       value="{{ $ev }}" id="ev_{{ $ep->id }}_{{ $loop->index }}"
                                       {{ in_array($ev, $ep->events ?? []) || in_array('*', $ep->events ?? []) ? 'checked' : '' }}>
                                <label class="form-check-label small" for="ev_{{ $ep->id }}_{{ $loop->index }}">
                                    <code>{{ $ev }}</code> — {{ $label }}
                                </label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="form-check form-switch">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                           id="active_{{ $ep->id }}" {{ $ep->is_active ? 'checked' : '' }}>
                    <label class="form-check-label" for="active_{{ $ep->id }}">Aktif</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endforeach
</div>
@endif

<!-- Logs Panel -->
<div id="logsPanel" class="mt-4" style="display:none;">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-bold"><i class="bi bi-journal-text me-2"></i>Log Pengiriman</h6>
            <button class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('logsPanel').style.display='none'">
                <i class="bi bi-x"></i>
            </button>
        </div>
        <div class="card-body p-0" id="logsContent">
            <div class="text-center py-4 text-muted small">Memuat log...</div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('settings.webhooks.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Tambah Webhook Endpoint</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nama <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="Contoh: HRIS System" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">URL Endpoint <span class="text-danger">*</span></label>
                    <input type="url" name="url" class="form-control" placeholder="https://your-system.com/webhook" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Events yang Dikirim <span class="text-danger">*</span></label>
                    <div class="row g-2">
                        @foreach(\App\Models\WebhookEndpoint::EVENTS as $ev => $label)
                        <div class="col-sm-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="events[]"
                                       value="{{ $ev }}" id="new_ev_{{ $loop->index }}" checked>
                                <label class="form-check-label small" for="new_ev_{{ $loop->index }}">
                                    <code>{{ $ev }}</code> — {{ $label }}
                                </label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="alert alert-info small mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Secret HMAC akan digenerate otomatis. Verifikasi signature di header <code>X-Helpdesk-Signature</code>.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Tambah</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleSecret(id, secret) {
    const el = document.getElementById('secret_' + id);
    el.textContent = el.textContent.startsWith('•') ? secret : '•'.repeat(20);
}

function testWebhook(id, btn) {
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    fetch(`/settings/webhooks/${id}/test`)
        .then(r => r.json())
        .then(d => alert(d.message))
        .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="bi bi-send"></i>'; });
}

function showLogs(id) {
    const panel = document.getElementById('logsPanel');
    const content = document.getElementById('logsContent');
    panel.style.display = 'block';
    content.innerHTML = '<div class="text-center py-4 text-muted small">Memuat log...</div>';
    panel.scrollIntoView({ behavior: 'smooth' });
    fetch(`/settings/webhooks/${id}/logs`)
        .then(r => r.json())
        .then(data => {
            if (!data.data.length) { content.innerHTML = '<div class="text-center py-4 text-muted small">Belum ada log.</div>'; return; }
            let html = '<table class="table table-sm mb-0"><thead><tr><th>Event</th><th>Status</th><th>Waktu</th><th>Keterangan</th></tr></thead><tbody>';
            data.data.forEach(log => {
                const ok = log.success;
                html += `<tr>
                    <td><code style="font-size:.72rem">${log.event}</code></td>
                    <td><span class="badge ${ok ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'}">${log.response_status ?? (log.error ? 'Error' : '—')}</span></td>
                    <td class="small text-muted">${log.sent_at}</td>
                    <td class="small text-muted" style="max-width:300px; overflow:hidden; text-overflow:ellipsis;">${log.error || log.response_body || ''}</td>
                </tr>`;
            });
            html += '</tbody></table>';
            content.innerHTML = html;
        });
}
</script>
@endpush
