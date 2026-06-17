@extends('layouts.app')
@section('title', 'Knowledge Base')
@section('breadcrumb')
    <li class="breadcrumb-item active">Knowledge Base</li>
@endsection
@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h1 class="page-title mb-0">Knowledge Base</h1>
        <p class="page-subtitle">Temukan solusi dan panduan sebelum membuat tiket baru.</p>
    </div>
    @if(!auth()->user()->isUser())
    <a href="{{ route('knowledge-base.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Tulis Artikel
    </a>
    @endif
</div>

<!-- Search Bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('knowledge-base.index') }}" class="d-flex gap-2">
            <div class="flex-grow-1">
                <input type="text" name="search" class="form-control" placeholder="Cari solusi, panduan, FAQ..."
                       value="{{ request('search') }}">
            </div>
            <select name="category_id" class="form-select" style="width:200px;">
                <option value="">Semua Kategori</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
            @if(request()->hasAny(['search','category_id']))
            <a href="{{ route('knowledge-base.index') }}" class="btn btn-outline-secondary">Reset</a>
            @endif
        </form>
    </div>
</div>

@if($articles->isEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-journal-x" style="font-size:2.5rem; opacity:.3;"></i>
        <p class="mt-2">{{ request('search') ? 'Tidak ada hasil untuk "' . request('search') . '"' : 'Belum ada artikel' }}</p>
        @if(!auth()->user()->isUser())
        <a href="{{ route('knowledge-base.create') }}" class="btn btn-primary btn-sm">Tulis Artikel Pertama</a>
        @endif
    </div>
</div>
@else
<div class="row g-3">
    @foreach($articles as $article)
    <div class="col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-2">
                    <div>
                        @if($article->category)
                        <span class="badge bg-primary-subtle text-primary small mb-1">{{ $article->category->name }}</span>
                        @endif
                        @if(!$article->is_published)
                        <span class="badge bg-secondary-subtle text-secondary small mb-1">Draft</span>
                        @endif
                    </div>
                    <span class="text-muted small"><i class="bi bi-eye me-1"></i>{{ $article->views }}</span>
                </div>
                <h6 class="fw-bold mb-1">
                    <a href="{{ route('knowledge-base.show', $article) }}" class="text-decoration-none text-dark">
                        {{ $article->title }}
                    </a>
                </h6>
                <p class="text-muted small mb-2">{{ $article->excerpt }}</p>
                @if($article->tags)
                <div class="d-flex flex-wrap gap-1 mb-2">
                    @foreach($article->tags as $tag)
                    <span class="badge bg-light text-secondary border" style="font-weight:400; font-size:.7rem;">{{ $tag }}</span>
                    @endforeach
                </div>
                @endif
            </div>
            <div class="card-footer bg-white border-top-0 pt-0 d-flex align-items-center justify-content-between">
                <span class="text-muted" style="font-size:.72rem;">
                    {{ $article->author->name }} · {{ $article->updated_at->diffForHumans() }}
                </span>
                <a href="{{ route('knowledge-base.show', $article) }}" class="btn btn-outline-primary btn-sm">
                    Baca <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
    @endforeach
</div>
<div class="mt-4">{{ $articles->links() }}</div>
@endif
@endsection
