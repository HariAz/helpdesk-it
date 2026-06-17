@extends('layouts.app')
@section('title', 'Tulis Artikel KB')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('knowledge-base.index') }}" class="text-decoration-none">Knowledge Base</a></li>
    <li class="breadcrumb-item active">Tulis Artikel</li>
@endsection
@section('content')
<div class="mb-4">
    <h1 class="page-title">Tulis Artikel Knowledge Base</h1>
</div>

<form method="POST" action="{{ route('knowledge-base.store') }}">
@csrf
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Judul Artikel <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" value="{{ old('title') }}"
                           placeholder="Contoh: Cara Reset Password Domain" required>
                </div>
                <div class="mb-0">
                    <label class="form-label">Konten Artikel <span class="text-danger">*</span></label>
                    <textarea name="content" class="form-control" rows="18"
                              placeholder="Tulis langkah-langkah solusi, penjelasan, dan tips di sini..."
                              required>{{ old('content') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold">Pengaturan Artikel</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Kategori</label>
                    <select name="category_id" class="form-select">
                        <option value="">-- Tidak ada --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tags</label>
                    <input type="text" name="tags" class="form-control" value="{{ old('tags') }}"
                           placeholder="password, reset, domain (pisah koma)">
                    <div class="form-text">Pisahkan dengan koma</div>
                </div>
                <div class="form-check form-switch">
                    <input type="hidden" name="is_published" value="0">
                    <input class="form-check-input" type="checkbox" name="is_published" value="1"
                           id="is_published" {{ old('is_published') ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="is_published">Publikasikan Sekarang</label>
                    <div class="form-text">Jika tidak dicentang, artikel disimpan sebagai draft.</div>
                </div>
            </div>
        </div>
        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i> Simpan Artikel
            </button>
            <a href="{{ route('knowledge-base.index') }}" class="btn btn-outline-secondary">Batal</a>
        </div>
    </div>
</div>
</form>
@endsection
