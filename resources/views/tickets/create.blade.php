@extends('layouts.app')

@section('title', 'Buat Tiket Baru')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}" class="text-decoration-none">Tiket</a></li>
    <li class="breadcrumb-item active">Buat Tiket Baru</li>
@endsection

@section('content')
<div class="mb-4">
    <h1 class="page-title">Buat Tiket Baru</h1>
    <p class="page-subtitle">Isi formulir berikut untuk melaporkan gangguan atau permintaan layanan IT.</p>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data">
            @csrf

            <!-- Template Picker -->
            <div class="card border-0 shadow-sm mb-3" id="templateCard">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-file-earmark-text me-2 text-primary"></i>Gunakan Template</h6>
                    <span class="badge bg-primary-subtle text-primary" style="font-size:.7rem;">Opsional</span>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-2">Pilih template untuk mengisi deskripsi secara otomatis.</p>
                    <div class="d-flex gap-2">
                        <select id="templateSelect" class="form-select form-select-sm">
                            <option value="">-- Pilih template (opsional) --</option>
                        </select>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="applyTemplate" disabled>
                            Terapkan
                        </button>
                    </div>
                    <div id="templateHint" class="text-muted small mt-2 d-none"></div>
                </div>
            </div>

            <!-- Judul & Deskripsi -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-pencil me-2"></i>Informasi Tiket</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Judul Masalah <span class="text-danger">*</span></label>
                        <input type="text" name="title" value="{{ old('title') }}"
                               class="form-control @error('title') is-invalid @enderror"
                               placeholder="Contoh: Printer tidak bisa mencetak di lantai 3"
                               required maxlength="255">
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Deskripsi Detail <span class="text-danger">*</span></label>
                        <textarea name="description" rows="6"
                                  class="form-control @error('description') is-invalid @enderror"
                                  placeholder="Jelaskan masalah secara rinci: kapan terjadi, dampaknya, langkah yang sudah dicoba, dll."
                                  required>{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <!-- Kategori & Prioritas -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-tags me-2"></i>Klasifikasi</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Kategori <span class="text-danger">*</span></label>
                            <select name="category_id" id="category_id"
                                    class="form-select @error('category_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Kategori --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Subkategori</label>
                            <select name="subcategory_id" id="subcategory_id" class="form-select" disabled>
                                <option value="">-- Pilih Subkategori --</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Prioritas <span class="text-danger">*</span></label>
                            <div class="row g-2">
                                @foreach(['rendah' => ['Rendah', 'secondary', 'Masalah ringan, tidak mengganggu produktivitas'], 'sedang' => ['Sedang', 'info', 'Mengganggu sebagian pekerjaan'], 'tinggi' => ['Tinggi', 'warning', 'Menghambat pekerjaan utama'], 'kritis' => ['Kritis', 'danger', 'Sistem down, dampak sangat luas']] as $val => [$lbl, $color, $desc])
                                    <div class="col-sm-6 col-xl-3">
                                        <input type="radio" class="btn-check" name="priority" id="p_{{ $val }}" value="{{ $val }}"
                                               {{ old('priority', 'sedang') === $val ? 'checked' : '' }}>
                                        <label class="btn btn-outline-{{ $color }} w-100 text-start py-2 px-3" for="p_{{ $val }}" style="border-radius:10px;">
                                            <div class="fw-bold small">{{ $lbl }}</div>
                                            <div style="font-size:.7rem; opacity:.8;">{{ $desc }}</div>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            @error('priority')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lampiran -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-paperclip me-2"></i>Lampiran (Opsional)</h6>
                </div>
                <div class="card-body">
                    <div class="border-2 border-dashed rounded-3 p-4 text-center" id="drop-zone"
                         style="border-color:#cbd5e1; cursor:pointer;">
                        <i class="bi bi-cloud-upload fs-2 text-muted"></i>
                        <p class="mt-2 mb-1 text-muted small">Seret file ke sini atau klik untuk memilih</p>
                        <p class="text-muted" style="font-size:.75rem;">Maks 3 file, masing-masing 5 MB (JPG, PNG, PDF, DOC, XLS)</p>
                        <input type="file" name="attachments[]" id="attachments" multiple class="d-none"
                               accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('attachments').click()">
                            Pilih File
                        </button>
                    </div>
                    <div id="file-list" class="mt-2"></div>
                    @error('attachments.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-send me-1"></i> Kirim Tiket
                </button>
                <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>

    <!-- Info sidebar -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-info-circle me-2 text-primary"></i>SLA per Prioritas</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Prioritas</th><th>Respons</th><th>Resolusi</th></tr></thead>
                    <tbody>
                        <tr><td><span class="badge priority-kritis">Kritis</span></td><td>1 jam</td><td>4 jam</td></tr>
                        <tr><td><span class="badge priority-tinggi">Tinggi</span></td><td>2 jam</td><td>8 jam</td></tr>
                        <tr><td><span class="badge priority-sedang">Sedang</span></td><td>4 jam</td><td>24 jam</td></tr>
                        <tr><td><span class="badge priority-rendah">Rendah</span></td><td>8 jam</td><td>72 jam</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-2"><i class="bi bi-lightbulb text-warning me-2"></i>Tips</h6>
                <ul class="list-unstyled small text-muted mb-0">
                    <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i>Tulis judul yang spesifik dan jelas</li>
                    <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i>Sertakan screenshot jika ada pesan error</li>
                    <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i>Sebutkan nomor komputer/lokasi Anda</li>
                    <li><i class="bi bi-check2 text-success me-1"></i>Pilih prioritas sesuai dampak masalah</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let allTemplates = [];

    // Load templates on page load
    fetch('/ticket-templates/list')
        .then(r => r.json())
        .then(data => { allTemplates = data; });

    // Category → Subcategory AJAX + template filter
    document.getElementById('category_id')?.addEventListener('change', function() {
        const sub = document.getElementById('subcategory_id');
        sub.innerHTML = '<option value="">-- Pilih Subkategori --</option>';
        sub.disabled = true;

        if (!this.value) return;

        fetch(`/categories/${this.value}/subcategories`)
            .then(r => r.json())
            .then(data => {
                if (data.length) {
                    data.forEach(item => {
                        sub.innerHTML += `<option value="${item.id}">${item.name}</option>`;
                    });
                    sub.disabled = false;
                }
            });

        // Filter templates by selected category
        const catId = parseInt(this.value);
        const tplSelect = document.getElementById('templateSelect');
        tplSelect.innerHTML = '<option value="">-- Pilih template (opsional) --</option>';
        const filtered = allTemplates.filter(t => t.category_id === catId);
        filtered.forEach(t => {
            tplSelect.innerHTML += `<option value="${t.id}" data-desc="${t.description_template.replace(/"/g,'&quot;')}" data-priority="${t.priority}">${t.name}</option>`;
        });
        document.getElementById('applyTemplate').disabled = true;
    });

    document.getElementById('templateSelect')?.addEventListener('change', function() {
        document.getElementById('applyTemplate').disabled = !this.value;
        const opt = this.options[this.selectedIndex];
        const hint = document.getElementById('templateHint');
        if (this.value) {
            hint.textContent = `Prioritas default: ${opt.dataset.priority}`;
            hint.classList.remove('d-none');
        } else {
            hint.classList.add('d-none');
        }
    });

    document.getElementById('applyTemplate')?.addEventListener('click', function() {
        const tplSelect = document.getElementById('templateSelect');
        const opt = tplSelect.options[tplSelect.selectedIndex];
        if (!opt.value) return;
        document.querySelector('textarea[name=description]').value = opt.dataset.desc;
        const priority = opt.dataset.priority;
        const radioId = `p_${priority}`;
        const radio = document.getElementById(radioId);
        if (radio) radio.checked = true;
        document.querySelector('textarea[name=description]').focus();
    });

    // File input preview
    document.getElementById('attachments')?.addEventListener('change', function() {
        const list = document.getElementById('file-list');
        list.innerHTML = '';
        Array.from(this.files).forEach(file => {
            const div = document.createElement('div');
            div.className = 'badge bg-light text-dark border me-1 mb-1 py-2 px-3';
            div.innerHTML = `<i class="bi bi-file-earmark me-1"></i>${file.name} <small class="text-muted">(${(file.size/1024).toFixed(0)} KB)</small>`;
            list.appendChild(div);
        });
    });

    // Drop zone
    const dropZone = document.getElementById('drop-zone');
    dropZone?.addEventListener('click', () => document.getElementById('attachments').click());
    dropZone?.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('border-primary'); });
    dropZone?.addEventListener('dragleave', () => dropZone.classList.remove('border-primary'));
    dropZone?.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('border-primary');
        document.getElementById('attachments').files = e.dataTransfer.files;
        document.getElementById('attachments').dispatchEvent(new Event('change'));
    });
</script>
@endpush
