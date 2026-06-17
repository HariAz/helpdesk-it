@extends('layouts.app')
@section('title', 'Edit Artikel KB')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('knowledge-base.index') }}" class="text-decoration-none">Knowledge Base</a></li>
    <li class="breadcrumb-item"><a href="{{ route('knowledge-base.show', $knowledgeBase) }}" class="text-decoration-none">{{ Str::limit($knowledgeBase->title, 30) }}</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection
@section('content')
<div class="mb-4">
    <h1 class="page-title">Edit Artikel</h1>
</div>

<form method="POST" action="{{ route('knowledge-base.update', $knowledgeBase) }}">
@csrf @method('PATCH')
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Judul Artikel <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control"
                           value="{{ old('title', $knowledgeBase->title) }}" required>
                </div>
                <div class="mb-0">
                    <label class="form-label">Konten Artikel <span class="text-danger">*</span></label>
                    <textarea name="content" class="form-control" rows="18" required>{{ old('content', $knowledgeBase->content) }}</textarea>
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
                            <option value="{{ $cat->id }}" {{ (old('category_id', $knowledgeBase->category_id)) == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tags</label>
                    <input type="text" name="tags" class="form-control"
                           value="{{ old('tags', $knowledgeBase->tags ? implode(', ', $knowledgeBase->tags) : '') }}"
                           placeholder="password, reset, domain">
                </div>
                <div class="form-check form-switch">
                    <input type="hidden" name="is_published" value="0">
                    <input class="form-check-input" type="checkbox" name="is_published" value="1"
                           id="is_published" {{ old('is_published', $knowledgeBase->is_published) ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="is_published">Dipublikasikan</label>
                </div>
            </div>
        </div>
        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i> Simpan Perubahan
            </button>
            <a href="{{ route('knowledge-base.show', $knowledgeBase) }}" class="btn btn-outline-secondary">Batal</a>
        </div>
    </div>
</div>
</form>
@endsection
