@extends('layouts.app')

@section('title', 'Sampah Tiket')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}" class="text-decoration-none">Tiket</a></li>
    <li class="breadcrumb-item active">Sampah</li>
@endsection

@section('content')
<div class="mb-4">
    <h1 class="page-title">Sampah Tiket</h1>
    <p class="page-subtitle">Tiket yang telah dihapus. Dapat dipulihkan kembali.</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($tickets->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-trash3 fs-1 text-muted"></i>
                <p class="mt-2 text-muted">Tidak ada tiket yang dihapus</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>No. Tiket</th>
                            <th>Judul</th>
                            <th>Pelapor</th>
                            <th>Dihapus</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tickets as $ticket)
                            <tr>
                                <td><span class="ticket-number">{{ $ticket->ticket_number }}</span></td>
                                <td class="small">{{ $ticket->title }}</td>
                                <td class="small">{{ $ticket->user->name }}</td>
                                <td class="small text-muted">{{ $ticket->deleted_at->diffForHumans() }}</td>
                                <td>
                                    <form method="POST" action="{{ route('tickets.restore', $ticket->id) }}" class="d-inline">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> Pulihkan
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-top">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
