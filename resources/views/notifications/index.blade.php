@extends('layouts.app')

@section('title', 'Notifikasi')

@section('breadcrumb')
    <li class="breadcrumb-item active">Notifikasi</li>
@endsection

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title mb-0">Notifikasi</h1>
        <p class="page-subtitle">Semua aktivitas yang berkaitan dengan Anda</p>
    </div>
    @if($notifications->total() > 0)
    <form method="POST" action="{{ route('notifications.read-all') }}">
        @csrf
        <button class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-check2-all me-1"></i> Tandai Semua Dibaca
        </button>
    </form>
    @endif
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @forelse($notifications as $notif)
        @php $data = $notif->data; $isRead = !is_null($notif->read_at); @endphp
        <div class="d-flex align-items-start gap-3 px-4 py-3 border-bottom {{ $isRead ? '' : 'bg-primary bg-opacity-5' }}"
             style="transition: background .15s;">
            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 bg-{{ $data['color'] ?? 'primary' }}-subtle text-{{ $data['color'] ?? 'primary' }}"
                 style="width:40px; height:40px; font-size:1.1rem;">
                <i class="bi {{ $data['icon'] ?? 'bi-bell' }}"></i>
            </div>
            <div class="flex-grow-1 min-w-0">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <p class="mb-0 small {{ $isRead ? 'text-muted' : 'fw-semibold' }}">
                            {{ $data['message'] ?? 'Notifikasi' }}
                        </p>
                        @if(!empty($data['ticket_number']))
                        <span class="badge bg-light text-secondary mt-1" style="font-size:.7rem; font-family:monospace;">
                            {{ $data['ticket_number'] }}
                        </span>
                        @endif
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        @if(!$isRead)
                        <span class="badge bg-primary rounded-pill" style="width:8px; height:8px; padding:0;"></span>
                        @endif
                        <span class="text-muted" style="font-size:.72rem; white-space:nowrap;">
                            {{ $notif->created_at->diffForHumans() }}
                        </span>
                    </div>
                </div>
            </div>
            @if(!empty($data['url']))
            <a href="{{ route('notifications.read', $notif->id) }}"
               class="btn btn-sm btn-outline-secondary flex-shrink-0">
                <i class="bi bi-arrow-right"></i>
            </a>
            @endif
        </div>
        @empty
        <div class="text-center py-5 text-muted">
            <i class="bi bi-bell-slash" style="font-size:2.5rem; opacity:.3;"></i>
            <p class="mt-2 mb-0">Belum ada notifikasi</p>
        </div>
        @endforelse
    </div>
    @if($notifications->hasPages())
    <div class="card-footer bg-white border-top-0 py-3">
        {{ $notifications->links() }}
    </div>
    @endif
</div>
@endsection
