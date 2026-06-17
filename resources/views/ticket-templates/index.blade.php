@extends('layouts.app')

@section('title', 'Template Tiket')

@section('breadcrumb')
    <li class="breadcrumb-item active">Template Tiket</li>
@endsection

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title mb-0">Template Tiket</h1>
        <p class="page-subtitle">Template deskripsi siap pakai untuk mempercepat pelaporan</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTemplateModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Template
    </button>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Nama Template</th>
                    <th>Kategori</th>
                    <th>Prioritas</th>
                    <th>Status</th>
                    <th>Dibuat oleh</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($templates as $tpl)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $tpl->name }}</div>
                        <div class="text-muted small text-truncate" style="max-width:300px;">{{ Str::limit($tpl->description_template, 80) }}</div>
                    </td>
                    <td>
                        {{ $tpl->category?->name ?? '—' }}
                        @if($tpl->subcategory) <span class="text-muted">/ {{ $tpl->subcategory->name }}</span> @endif
                    </td>
                    <td><span class="badge priority-{{ $tpl->priority }}">{{ ucfirst($tpl->priority) }}</span></td>
                    <td>
                        @if($tpl->is_active)
                            <span class="badge bg-success-subtle text-success">Aktif</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                        @endif
                    </td>
                    <td class="text-muted small">{{ $tpl->creator->name }}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-secondary"
                                data-bs-toggle="modal"
                                data-bs-target="#editModal{{ $tpl->id }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="{{ route('ticket-templates.destroy', $tpl) }}" class="d-inline"
                              onsubmit="return confirm('Hapus template ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>

                <!-- Edit Modal -->
                <div class="modal fade" id="editModal{{ $tpl->id }}" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <form method="POST" action="{{ route('ticket-templates.update', $tpl) }}" class="modal-content">
                            @csrf @method('PATCH')
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Template</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Nama Template</label>
                                    <input type="text" name="name" class="form-control" value="{{ $tpl->name }}" required>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Kategori</label>
                                        <select name="category_id" class="form-select" required>
                                            @foreach($categories as $cat)
                                                <option value="{{ $cat->id }}" {{ $tpl->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Prioritas</label>
                                        <select name="priority" class="form-select" required>
                                            @foreach(['kritis','tinggi','sedang','rendah'] as $p)
                                                <option value="{{ $p }}" {{ $tpl->priority === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Template Deskripsi</label>
                                    <textarea name="description_template" class="form-control" rows="5" required>{{ $tpl->description_template }}</textarea>
                                    <div class="form-text">Gunakan placeholder seperti [nama device], [nomor seri], dll.</div>
                                </div>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active{{ $tpl->id }}" {{ $tpl->is_active ? 'checked' : '' }}>
                                    <label class="form-check-label" for="active{{ $tpl->id }}">Template Aktif</label>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
                @empty
                <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada template</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($templates->hasPages())
    <div class="card-footer bg-white">{{ $templates->links() }}</div>
    @endif
</div>

<!-- Add Template Modal -->
<div class="modal fade" id="addTemplateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('ticket-templates.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Tambah Template Tiket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nama Template</label>
                    <input type="text" name="name" class="form-control" placeholder="Contoh: Masalah Printer Kantor" required>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Kategori</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">Pilih kategori...</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @foreach($cat->children as $sub)
                                    <option value="{{ $sub->id }}" disabled>&nbsp;&nbsp;&nbsp;{{ $sub->name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Prioritas Default</label>
                        <select name="priority" class="form-select" required>
                            <option value="sedang" selected>Sedang</option>
                            <option value="kritis">Kritis</option>
                            <option value="tinggi">Tinggi</option>
                            <option value="rendah">Rendah</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Template Deskripsi</label>
                    <textarea name="description_template" class="form-control" rows="6"
                              placeholder="Tulis template deskripsi masalah. Gunakan [placeholder] untuk bagian yang perlu diisi user..."
                              required></textarea>
                    <div class="form-text">Contoh: Printer [nama printer] di [lokasi] tidak dapat mencetak sejak [tanggal].</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Template</button>
            </div>
        </form>
    </div>
</div>
@endsection
