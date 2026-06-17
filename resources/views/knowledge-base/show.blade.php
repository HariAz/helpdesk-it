@extends('layouts.app')
@section('title', $knowledgeBase->title)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('knowledge-base.index') }}" class="text-decoration-none">Knowledge Base</a></li>
    <li class="breadcrumb-item active">{{ Str::limit($knowledgeBase->title, 40) }}</li>
@endsection
@section('content')
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                @if($knowledgeBase->category)
                <span class="badge bg-primary-subtle text-primary mb-2">{{ $knowledgeBase->category->name }}</span>
                @endif
                @if(!$knowledgeBase->is_published)
                <span class="badge bg-warning-subtle text-warning mb-2">Draft — belum dipublikasi</span>
                @endif

                <h1 style="font-size:1.5rem; font-weight:700;" class="mb-2">{{ $knowledgeBase->title }}</h1>

                <div class="d-flex align-items-center gap-3 text-muted small mb-4 pb-3 border-bottom">
                    <span><i class="bi bi-person me-1"></i>{{ $knowledgeBase->author->name }}</span>
                    <span><i class="bi bi-calendar3 me-1"></i>{{ $knowledgeBase->updated_at->isoFormat('D MMMM YYYY') }}</span>
                    <span><i class="bi bi-eye me-1"></i>{{ $knowledgeBase->views }} kali dilihat</span>
                </div>

                <div class="article-content" style="line-height:1.8;">
                    {!! nl2br(e($knowledgeBase->content)) !!}
                </div>

                @if($knowledgeBase->tags)
                <div class="mt-4 pt-3 border-top">
                    <span class="text-muted small me-2">Tag:</span>
                    @foreach($knowledgeBase->tags as $tag)
                    <span class="badge bg-light text-secondary border me-1" style="font-weight:400;">{{ $tag }}</span>
                    @endforeach
                </div>
                @endif
            </div>
            @if(!auth()->user()->isUser())
            <div class="card-footer bg-white d-flex gap-2">
                <a href="{{ route('knowledge-base.edit', $knowledgeBase) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
                <form method="POST" action="{{ route('knowledge-base.destroy', $knowledgeBase) }}"
                      onsubmit="return confirm('Hapus artikel ini?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i> Hapus</button>
                </form>
            </div>
            @endif
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Attach to ticket (teknisi/supervisor) -->
        @if(!auth()->user()->isUser())
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-link me-2"></i>Lampirkan ke Tiket</h6>
            </div>
            <div class="card-body">
                <form method="POST" id="attachForm" class="d-flex gap-2">
                    @csrf
                    <input type="text" id="ticketSearch" class="form-control form-control-sm"
                           placeholder="Cari no. tiket..." autocomplete="off">
                    <button type="submit" class="btn btn-primary btn-sm" id="attachBtn" disabled>
                        <i class="bi bi-paperclip"></i>
                    </button>
                </form>
                <div id="ticketSuggestions" class="list-group mt-1 shadow-sm" style="display:none; position:absolute; z-index:1000; width:calc(100% - 3rem);"></div>
                <input type="hidden" id="selectedTicketId">
            </div>
        </div>
        @endif

        <!-- Related articles -->
        @if($related->count())
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-journals me-2"></i>Artikel Terkait</h6>
            </div>
            <div class="card-body p-0">
                @foreach($related as $rel)
                <a href="{{ route('knowledge-base.show', $rel) }}"
                   class="d-flex align-items-start gap-2 px-3 py-2 border-bottom text-decoration-none text-dark">
                    <i class="bi bi-file-text text-muted mt-1 flex-shrink-0"></i>
                    <div>
                        <div class="small fw-semibold">{{ $rel->title }}</div>
                        <div class="text-muted" style="font-size:.72rem;">{{ $rel->views }} views</div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

@if(session('success'))
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div class="toast show align-items-center text-white bg-success border-0">
        <div class="d-flex">
            <div class="toast-body">{{ session('success') }}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
let selectedTicketId = null;
const searchInput = document.getElementById('ticketSearch');
const suggestions = document.getElementById('ticketSuggestions');
const attachBtn = document.getElementById('attachBtn');
const attachForm = document.getElementById('attachForm');

searchInput?.addEventListener('input', function() {
    const q = this.value.trim();
    if (q.length < 2) { suggestions.style.display = 'none'; return; }

    fetch(`/tickets?search=${encodeURIComponent(q)}&format=json`)
        .then(r => r.json())
        .catch(() => null)
        .then(data => {
            if (!data) return;
            suggestions.innerHTML = '';
            if (data.length === 0) {
                suggestions.innerHTML = '<div class="list-group-item text-muted small">Tidak ditemukan</div>';
            }
            data.forEach(t => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'list-group-item list-group-item-action small';
                item.innerHTML = `<span class="fw-bold font-monospace">${t.ticket_number}</span> — ${t.title}`;
                item.addEventListener('click', () => {
                    selectedTicketId = t.id;
                    searchInput.value = t.ticket_number + ' — ' + t.title;
                    suggestions.style.display = 'none';
                    attachBtn.disabled = false;
                });
                suggestions.appendChild(item);
            });
            suggestions.style.display = 'block';
        });
});

attachForm?.addEventListener('submit', function(e) {
    e.preventDefault();
    if (!selectedTicketId) return;
    fetch(`/tickets/${selectedTicketId}/kb-attach`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content},
        body: JSON.stringify({ kb_article_id: {{ $knowledgeBase->id }} })
    }).then(r => r.redirected ? location.reload() : r.json()).then(() => {
        alert('Artikel berhasil dilampirkan ke tiket.');
        searchInput.value = '';
        attachBtn.disabled = true;
        selectedTicketId = null;
    });
});

document.addEventListener('click', e => {
    if (!e.target.closest('#ticketSearch')) suggestions.style.display = 'none';
});
</script>
@endpush
