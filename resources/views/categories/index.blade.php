@extends('layouts.app')

@section('title', 'Kategori Tiket')

@section('breadcrumb')
    <li class="breadcrumb-item active">Kategori</li>
@endsection

@section('content')
<div class="d-flex align-items-start justify-content-between mb-4">
    <div>
        <h1 class="page-title">Kategori Tiket</h1>
        <p class="page-subtitle">Kelola kategori dan subkategori untuk klasifikasi tiket.</p>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <i class="bi bi-plus-circle me-1"></i> Tambah Kategori
    </button>
</div>

<div class="row g-3">
    @forelse($categories as $cat)
    <div class="col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center gap-2">
                @if($cat->icon)
                    <i class="bi bi-{{ $cat->icon }} text-primary fs-5"></i>
                @endif
                <h6 class="mb-0 fw-bold flex-grow-1">{{ $cat->name }}</h6>
                <span class="badge {{ $cat->is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                    {{ $cat->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>
            <div class="card-body p-0">
                @if($cat->children->isEmpty())
                    <div class="text-muted text-center py-3 small">Belum ada subkategori</div>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach($cat->children as $sub)
                        <li class="list-group-item d-flex align-items-center gap-2 py-2 px-3">
                            <i class="bi bi-dash text-muted"></i>
                            <span class="small flex-grow-1">{{ $sub->name }}</span>
                            <span class="badge {{ $sub->is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}" style="font-size:.65rem;">
                                {{ $sub->is_active ? '✓' : '✗' }}
                            </span>
                        </li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <div class="card-footer bg-white border-top py-2">
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary"
                            onclick="openEditModal({{ $cat->id }}, '{{ $cat->name }}', '{{ $cat->icon }}')">
                        <i class="bi bi-pencil"></i> Edit
                    </button>
                    <button class="btn btn-sm btn-outline-primary"
                            onclick="openSubModal({{ $cat->id }}, '{{ $cat->name }}')">
                        <i class="bi bi-plus me-1"></i>Subkategori
                    </button>
                </div>
            </div>
        </div>
    </div>
    @empty
        <div class="col-12 text-center py-5 text-muted">
            <i class="bi bi-tags fs-1"></i>
            <p class="mt-2">Belum ada kategori. Klik tombol "Tambah Kategori" untuk memulai.</p>
        </div>
    @endforelse
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('categories.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Kategori Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="Contoh: Hardware">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Icon (Bootstrap Icons)</label>
                        <input type="text" name="icon" class="form-control" placeholder="Contoh: pc-display, printer, wifi">
                        <div class="form-text">Cek di <a href="https://icons.getbootstrap.com" target="_blank">icons.getbootstrap.com</a></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Tambah</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Subcategory Modal -->
<div class="modal fade" id="addSubModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('categories.store') }}">
                @csrf
                <input type="hidden" name="parent_id" id="sub_parent_id">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Subkategori — <span id="sub_parent_name"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Subkategori <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Tambah</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="editCategoryForm">
                @csrf @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Kategori</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Icon</label>
                        <input type="text" name="icon" id="edit_icon" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openSubModal(parentId, parentName) {
    document.getElementById('sub_parent_id').value = parentId;
    document.getElementById('sub_parent_name').textContent = parentName;
    new bootstrap.Modal(document.getElementById('addSubModal')).show();
}

function openEditModal(id, name, icon) {
    document.getElementById('editCategoryForm').action = `/categories/${id}`;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_icon').value = icon || '';
    new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
}
</script>
@endpush
