@extends('layouts.app')

@section('title', 'Manajemen Pengguna')

@section('breadcrumb')
    <li class="breadcrumb-item active">Pengguna</li>
@endsection

@section('content')
<div class="d-flex align-items-start justify-content-between mb-4">
    <div>
        <h1 class="page-title">Manajemen Pengguna</h1>
        <p class="page-subtitle">Kelola akun dan hak akses pengguna sistem.</p>
    </div>
    <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-person-plus me-1"></i> Tambah Pengguna
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Departemen</th>
                        <th>Tiket</th>
                        <th>Terakhir Login</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr class="{{ !$user->is_active ? 'opacity-50' : '' }}">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white flex-shrink-0"
                                         style="width:34px; height:34px; font-size:.72rem;
                                                background: {{ $user->role === 'supervisor' ? '#1a56db' : ($user->role === 'teknisi' ? '#16a34a' : '#64748b') }};">
                                        {{ $user->initials }}
                                    </div>
                                    <div>
                                        <div class="fw-semibold small">{{ $user->name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="small">{{ $user->email }}</td>
                            <td>
                                @php $roleColors = ['supervisor' => 'primary', 'teknisi' => 'success', 'user' => 'secondary']; @endphp
                                <span class="badge bg-{{ $roleColors[$user->role] }}-subtle text-{{ $roleColors[$user->role] }}">
                                    {{ ucfirst($user->role) }}
                                </span>
                            </td>
                            <td class="small text-muted">{{ $user->department ?? '—' }}</td>
                            <td>
                                @if($user->role === 'teknisi')
                                    <span class="small">{{ $user->total_assigned ?? 0 }} / {{ $user->resolved_count ?? 0 }} selesai</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="small text-muted">
                                {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Belum pernah' }}
                            </td>
                            <td>
                                @if($user->is_active)
                                    <span class="badge bg-success-subtle text-success"><i class="bi bi-circle-fill me-1" style="font-size:.5rem;"></i>Aktif</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger"><i class="bi bi-circle-fill me-1" style="font-size:.5rem;"></i>Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @if($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('users.toggle', $user) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm {{ $user->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                                title="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                            <i class="bi bi-{{ $user->is_active ? 'pause-circle' : 'play-circle' }}"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-top">
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection
