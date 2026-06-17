@extends('layouts.app')
@section('title', 'API Token')
@section('breadcrumb')
    <li class="breadcrumb-item active">API Token</li>
@endsection
@section('content')
<div class="mb-4">
    <h1 class="page-title">API Token</h1>
    <p class="page-subtitle">Kelola token untuk akses REST API Helpdesk IT.</p>
</div>

@if(session('new_token'))
<div class="alert alert-success border-0 shadow-sm">
    <h6 class="fw-bold mb-1"><i class="bi bi-key-fill me-2"></i>Token Baru: <strong>{{ session('token_name') }}</strong></h6>
    <p class="small mb-2 text-danger">Salin token ini sekarang — tidak akan ditampilkan lagi.</p>
    <div class="input-group">
        <input type="text" class="form-control form-control-sm font-monospace" id="newToken"
               value="{{ session('new_token') }}" readonly>
        <button class="btn btn-outline-secondary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('newToken').value); this.textContent='Disalin!'">
            <i class="bi bi-clipboard"></i> Salin
        </button>
    </div>
</div>
@endif

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-plus-circle me-2 text-primary"></i>Buat Token Baru</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('settings.api-tokens.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Nama Token <span class="text-danger">*</span></label>
                        <input type="text" name="token_name" class="form-control" placeholder="Contoh: HRIS Integration" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hak Akses</label>
                        @foreach([
                            '*'              => 'Semua akses',
                            'tickets:read'   => 'Baca tiket',
                            'tickets:write'  => 'Buat/ubah tiket',
                            'stats:read'     => 'Baca statistik',
                        ] as $ability => $label)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="abilities[]"
                                   value="{{ $ability }}" id="ab_{{ $ability }}"
                                   {{ $ability === '*' ? 'checked' : '' }}>
                            <label class="form-check-label small" for="ab_{{ $ability }}">
                                <code>{{ $ability }}</code> — {{ $label }}
                            </label>
                        </div>
                        @endforeach
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-plus-circle me-1"></i> Buat Token
                    </button>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-book me-2 text-secondary"></i>Dokumentasi API</h6>
            </div>
            <div class="card-body small text-muted">
                <p class="mb-2">Base URL: <code>{{ url('/api/v1') }}</code></p>
                <p class="mb-1 fw-semibold">Authentication:</p>
                <pre class="bg-light p-2 rounded" style="font-size:.72rem;">Authorization: Bearer {token}</pre>
                <p class="mb-1 fw-semibold mt-2">Endpoints:</p>
                <ul class="mb-0 ps-3" style="line-height:1.8;">
                    <li><code>POST /auth/token</code> — issue token</li>
                    <li><code>DELETE /auth/token</code> — revoke token</li>
                    <li><code>GET /tickets</code> — list tiket</li>
                    <li><code>POST /tickets</code> — buat tiket</li>
                    <li><code>GET /tickets/{no}</code> — detail tiket</li>
                    <li><code>PATCH /tickets/{no}/status</code> — ubah status</li>
                    <li><code>PATCH /tickets/{no}/assign</code> — assign teknisi</li>
                    <li><code>GET /stats</code> — statistik (supervisor)</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-list-ul me-2"></i>Token Aktif ({{ $tokens->count() }})</h6>
            </div>
            <div class="card-body p-0">
                @forelse($tokens as $token)
                <div class="d-flex align-items-center gap-3 px-3 py-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div class="flex-grow-1">
                        <div class="fw-semibold small">{{ $token->name }}</div>
                        <div class="text-muted" style="font-size:.72rem;">
                            Dibuat: {{ $token->created_at->isoFormat('D MMM YYYY') }}
                            &nbsp;·&nbsp;
                            Terakhir digunakan: {{ $token->last_used_at ? $token->last_used_at->diffForHumans() : 'Belum pernah' }}
                        </div>
                        <div class="mt-1">
                            @foreach($token->abilities ?? ['*'] as $ab)
                            <span class="badge bg-secondary-subtle text-secondary" style="font-size:.65rem;">{{ $ab }}</span>
                            @endforeach
                        </div>
                    </div>
                    <form method="POST" action="{{ route('settings.api-tokens.destroy', $token->id) }}">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" title="Cabut token"
                                onclick="return confirm('Cabut token \'{{ $token->name }}\'?')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
                @empty
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-key fs-2"></i>
                    <p class="mt-2 mb-0 small">Belum ada token. Buat token untuk mulai menggunakan API.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
