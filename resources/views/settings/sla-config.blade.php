@extends('layouts.app')
@section('title', 'Konfigurasi SLA')
@section('breadcrumb')
    <li class="breadcrumb-item active">Konfigurasi SLA</li>
@endsection
@section('content')
<div class="mb-4">
    <h1 class="page-title">Konfigurasi SLA</h1>
    <p class="page-subtitle">Atur batas waktu respons dan resolusi per prioritas. Override per kategori untuk kasus khusus.</p>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<!-- Global SLA -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-globe me-2 text-primary"></i>SLA Global (berlaku untuk semua kategori)</h6>
    </div>
    <form method="POST" action="{{ route('settings.sla.update') }}">
        @csrf
        <div class="card-body p-0">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Prioritas</th>
                        <th>Waktu Respons (jam)</th>
                        <th>Waktu Resolusi (jam)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($globalConfigs as $i => $cfg)
                    <input type="hidden" name="configs[{{ $i }}][priority]" value="{{ $cfg->priority }}">
                    <input type="hidden" name="configs[{{ $i }}][category_id]" value="">
                    <tr>
                        <td><span class="badge priority-{{ $cfg->priority }} px-3">{{ ucfirst($cfg->priority) }}</span></td>
                        <td>
                            <input type="number" name="configs[{{ $i }}][response_time_hours]"
                                   class="form-control form-control-sm" style="width:100px;"
                                   value="{{ $cfg->response_time_hours }}" min="0.5" step="0.5" required>
                        </td>
                        <td>
                            <input type="number" name="configs[{{ $i }}][resolution_time_hours]"
                                   class="form-control form-control-sm" style="width:100px;"
                                   value="{{ $cfg->resolution_time_hours }}" min="0.5" step="0.5" required>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white text-end">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-save me-1"></i> Simpan SLA Global
            </button>
        </div>
    </form>
</div>

<!-- Category-specific overrides -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold"><i class="bi bi-tags me-2 text-warning"></i>Override SLA per Kategori</h6>
        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addOverrideModal">
            <i class="bi bi-plus-lg me-1"></i> Tambah Override
        </button>
    </div>
    <div class="card-body p-0">
        @if($categoryConfigs->isEmpty())
        <p class="text-center text-muted py-4 small">Belum ada override per kategori. Semua tiket menggunakan SLA global.</p>
        @else
        <table class="table mb-0 align-middle">
            <thead><tr><th>Kategori</th><th>Prioritas</th><th>Respons (jam)</th><th>Resolusi (jam)</th><th></th></tr></thead>
            <tbody>
                @foreach($categoryConfigs as $cfg)
                <tr>
                    <td class="fw-semibold">{{ $cfg->category?->name ?? '—' }}</td>
                    <td><span class="badge priority-{{ $cfg->priority }}">{{ ucfirst($cfg->priority) }}</span></td>
                    <td>{{ $cfg->response_time_hours }}</td>
                    <td>{{ $cfg->resolution_time_hours }}</td>
                    <td>
                        <form method="POST" action="{{ route('settings.sla.destroy', $cfg) }}" class="d-inline"
                              onsubmit="return confirm('Hapus override ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

<!-- Add Override Modal -->
<div class="modal fade" id="addOverrideModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('settings.sla.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Tambah Override SLA per Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Kategori</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">Pilih kategori...</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Prioritas</label>
                    <select name="priority" class="form-select" required>
                        @foreach(['kritis','tinggi','sedang','rendah'] as $p)
                            <option value="{{ $p }}">{{ ucfirst($p) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label">Waktu Respons (jam)</label>
                        <input type="number" name="response_time_hours" class="form-control" min="0.5" step="0.5" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Waktu Resolusi (jam)</label>
                        <input type="number" name="resolution_time_hours" class="form-control" min="0.5" step="0.5" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Override</button>
            </div>
        </form>
    </div>
</div>
@endsection
