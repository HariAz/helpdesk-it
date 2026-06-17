@extends('layouts.app')

@section('title', 'Edit Pengguna')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('users.index') }}" class="text-decoration-none">Pengguna</a></li>
    <li class="breadcrumb-item active">{{ $user->name }}</li>
@endsection

@section('content')
<div class="mb-4">
    <h1 class="page-title">Edit Pengguna</h1>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="POST" action="{{ route('users.update', $user) }}">
                    @csrf @method('PATCH')
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}"
                               class="form-control @error('name') is-invalid @enderror" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}"
                               class="form-control @error('email') is-invalid @enderror" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="user" {{ $user->role === 'user' ? 'selected' : '' }}>User</option>
                            <option value="teknisi" {{ $user->role === 'teknisi' ? 'selected' : '' }}>Teknisi</option>
                            <option value="supervisor" {{ $user->role === 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label">Departemen</label>
                            <input type="text" name="department" value="{{ old('department', $user->department) }}" class="form-control">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">No. Telepon</label>
                            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control">
                        </div>
                    </div>
                    <hr>
                    <p class="text-muted small mb-3">Kosongkan jika tidak ingin mengubah password</p>
                    <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="password"
                               class="form-control @error('password') is-invalid @enderror" minlength="8">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" class="form-control">
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($user->role === 'teknisi')
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold">Statistik Teknisi</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-center">
                            <div class="stat-value fs-3">{{ $stats['total'] }}</div>
                            <div class="stat-label">Total Tiket</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <div class="stat-value fs-3 text-success">{{ $stats['resolved'] }}</div>
                            <div class="stat-label">Diselesaikan</div>
                        </div>
                    </div>
                </div>
                @if($stats['avg_rating'])
                    <hr>
                    <div class="text-center">
                        <div class="text-warning fs-4">
                            @for($s=1; $s<=5; $s++)
                                <i class="bi bi-star{{ $s <= round($stats['avg_rating']) ? '-fill' : '' }}"></i>
                            @endfor
                        </div>
                        <div class="text-muted small">Rata-rata: {{ number_format($stats['avg_rating'], 1) }} / 5</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
