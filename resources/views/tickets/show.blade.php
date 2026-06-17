@extends('layouts.app')

@section('title', $ticket->ticket_number)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}" class="text-decoration-none">Tiket</a></li>
    <li class="breadcrumb-item active">{{ $ticket->ticket_number }}</li>
@endsection

@push('styles')
<style>
    .comment-bubble { border-radius: 12px; }
    .sla-countdown { font-size: 1.5rem; font-weight: 700; }
</style>
@endpush

@section('content')
@php
    $user = auth()->user();
    $isActive = !in_array($ticket->status, ['closed', 'cancelled']);
    $slaStatus = 'ok';
    if ($ticket->sla_deadline && $isActive) {
        $pct = (now()->diffInMinutes($ticket->created_at) / max(1, $ticket->sla_deadline->diffInMinutes($ticket->created_at))) * 100;
        if ($pct >= 100) $slaStatus = 'overdue';
        elseif ($pct >= 75) $slaStatus = 'warning';
    }
@endphp

<!-- Header -->
<div class="d-flex align-items-start justify-content-between gap-3 mb-4 flex-wrap">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="ticket-number fs-6">{{ $ticket->ticket_number }}</span>
            <span class="badge priority-{{ $ticket->priority }} px-2">{{ ucfirst($ticket->priority) }}</span>
            <span class="badge bg-{{ \App\Models\Ticket::STATUS_COLORS[$ticket->status] ?? 'secondary' }}-subtle text-{{ \App\Models\Ticket::STATUS_COLORS[$ticket->status] ?? 'secondary' }}">
                {{ \App\Models\Ticket::STATUS_LABELS[$ticket->status] ?? $ticket->status }}
            </span>
            @if($ticket->is_escalated)
                <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>Eskalasi</span>
            @endif
        </div>
        <h1 class="page-title mb-0">{{ $ticket->title }}</h1>
        <p class="page-subtitle mt-1">
            Dibuat oleh <strong>{{ $ticket->user->name }}</strong>
            · {{ $ticket->created_at->isoFormat('D MMMM YYYY, HH:mm') }}
            @if($ticket->category)
                · {{ $ticket->category->name }}
                @if($ticket->subcategory) / {{ $ticket->subcategory->name }} @endif
            @endif
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        @if($user->isSupervisor() && $isActive)
            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal">
                <i class="bi bi-x-circle me-1"></i> Cancel
            </button>
        @endif
    </div>
</div>

<div class="row g-4">
    <!-- Main Column -->
    <div class="col-lg-8">

        <!-- Description -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center gap-2">
                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold"
                     style="width:32px; height:32px; font-size:.75rem;">
                    {{ $ticket->user->initials }}
                </div>
                <div>
                    <div class="fw-semibold small">{{ $ticket->user->name }}</div>
                    <div class="text-muted" style="font-size:.72rem;">{{ $ticket->created_at->isoFormat('D MMM YYYY, HH:mm') }}</div>
                </div>
            </div>
            <div class="card-body">
                <div style="white-space: pre-wrap; font-size:.9rem;">{{ $ticket->description }}</div>
            </div>
            @if($ticket->attachments->where('comment_id', null)->count() > 0)
            <div class="card-footer bg-white border-top">
                <div class="d-flex flex-wrap gap-2">
                    @foreach($ticket->attachments->where('comment_id', null) as $att)
                        <a href="{{ route('attachments.download', $att) }}"
                           class="badge bg-light text-dark border py-2 px-3 text-decoration-none">
                            <i class="bi bi-file-earmark me-1"></i>
                            {{ $att->original_name }}
                            <span class="text-muted ms-1">({{ $att->size_formatted }})</span>
                        </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Update Status (Teknisi/Supervisor) -->
        @if(($user->isTeknisi() || $user->isSupervisor()) && $isActive)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-arrow-right-circle me-2"></i>Update Status</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('tickets.status', $ticket) }}">
                    @csrf @method('PATCH')
                    <div class="row g-3">
                        <div class="col-sm-5">
                            <label class="form-label">Status Baru</label>
                            <select name="status" class="form-select">
                                @php
                                    $allowed = match($ticket->status) {
                                        'open' => ['assigned', 'in_progress'],
                                        'assigned' => ['in_progress'],
                                        'in_progress' => ['pending_user', 'resolved'],
                                        'pending_user' => ['in_progress', 'resolved'],
                                        'resolved' => ['reopened', 'closed'],
                                        'reopened' => ['in_progress'],
                                        default => [],
                                    };
                                    if ($user->isSupervisor()) $allowed[] = 'cancelled';
                                @endphp
                                @foreach($allowed as $s)
                                    <option value="{{ $s }}">{{ \App\Models\Ticket::STATUS_LABELS[$s] ?? $s }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-7">
                            <label class="form-label">Catatan <span class="text-danger">*</span></label>
                            <input type="text" name="note" class="form-control" placeholder="Jelaskan perubahan status..." required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm mt-3">
                        <i class="bi bi-arrow-right-circle me-1"></i> Update Status
                    </button>
                </form>
            </div>
        </div>
        @endif

        <!-- Assign (Supervisor) -->
        @if($user->isSupervisor() && $isActive)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-person-plus me-2"></i>Assign ke Teknisi</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('tickets.assign', $ticket) }}">
                    @csrf @method('PATCH')
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Teknisi</label>
                            <select name="assigned_to" class="form-select" required>
                                <option value="">-- Pilih Teknisi --</option>
                                @foreach($teknisi as $tek)
                                    <option value="{{ $tek->id }}" {{ $ticket->assigned_to == $tek->id ? 'selected' : '' }}>
                                        {{ $tek->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Catatan (opsional)</label>
                            <input type="text" name="note" class="form-control" placeholder="Instruksi untuk teknisi...">
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3 mt-3">
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="bi bi-person-check me-1"></i> Assign
                        </button>
                        @if($ticket->assignee)
                            <span class="text-muted small">Saat ini: <strong>{{ $ticket->assignee->name }}</strong></span>
                        @endif
                    </div>
                </form>
            </div>
        </div>
        @endif

        <!-- Timeline / Comments -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <ul class="nav nav-tabs card-header-tabs border-0">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#tab-comments">
                            <i class="bi bi-chat me-1"></i> Komentar ({{ $ticket->publicComments->count() }})
                        </a>
                    </li>
                    @if($user->isTeknisi() || $user->isSupervisor())
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-internal">
                            <i class="bi bi-lock me-1"></i> Catatan Internal ({{ $ticket->internalNotes->count() }})
                        </a>
                    </li>
                    @endif
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-timeline">
                            <i class="bi bi-clock-history me-1"></i> Timeline
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <!-- Public Comments -->
                    <div class="tab-pane fade show active" id="tab-comments">
                        <div class="mb-4">
                            @forelse($ticket->publicComments as $comment)
                                <div class="d-flex gap-3 mb-3">
                                    <div class="rounded-circle bg-{{ $comment->user->isTeknisi() ? 'success' : 'primary' }} bg-opacity-10
                                                text-{{ $comment->user->isTeknisi() ? 'success' : 'primary' }}
                                                d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                                         style="width:36px; height:36px; font-size:.75rem; align-self:flex-start; margin-top:.25rem;">
                                        {{ $comment->user->initials }}
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="card comment-bubble comment-public">
                                            <div class="card-body py-2 px-3">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="fw-semibold small">{{ $comment->user->name }}</span>
                                                    <span class="text-muted" style="font-size:.72rem;">{{ $comment->created_at->diffForHumans() }}</span>
                                                </div>
                                                <div style="font-size:.875rem; white-space:pre-wrap;">{!! preg_replace('/@([\w\s]{2,40})/', '<span class="badge bg-warning-subtle text-warning-emphasis">@$1</span>', e($comment->body)) !!}</div>
                                                @if($comment->attachments->count())
                                                <div class="mt-2 d-flex flex-wrap gap-1">
                                                    @foreach($comment->attachments as $att)
                                                    <a href="{{ route('attachments.download', $att) }}"
                                                       class="badge bg-light text-secondary border text-decoration-none d-flex align-items-center gap-1"
                                                       style="font-weight:400; font-size:.75rem;">
                                                        <i class="bi bi-paperclip"></i> {{ $att->original_name }}
                                                    </a>
                                                    @endforeach
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted text-center py-3 small">Belum ada komentar</p>
                            @endforelse
                        </div>

                        @if($isActive || $user->isSupervisor())
                        <form method="POST" action="{{ route('comments.store', $ticket) }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="is_internal" value="0">
                            <div class="d-flex gap-2">
                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                                     style="width:36px; height:36px; font-size:.75rem;">
                                    {{ $user->initials }}
                                </div>
                                <div class="flex-grow-1">
                                    <textarea name="body" id="commentBody" class="form-control" rows="3"
                                              placeholder="Tulis komentar... Gunakan @nama untuk mention seseorang" required></textarea>
                                    <div class="d-flex align-items-center justify-content-between mt-2 gap-2 flex-wrap">
                                        <label class="btn btn-outline-secondary btn-sm mb-0" style="cursor:pointer;">
                                            <i class="bi bi-paperclip me-1"></i> Lampiran
                                            <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.zip" class="d-none" id="commentFiles">
                                        </label>
                                        <div id="filePreview" class="d-flex flex-wrap gap-1 flex-grow-1"></div>
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="bi bi-send me-1"></i> Kirim
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        @endif
                    </div>

                    <!-- Internal Notes -->
                    @if($user->isTeknisi() || $user->isSupervisor())
                    <div class="tab-pane fade" id="tab-internal">
                        <div class="mb-4">
                            @forelse($ticket->internalNotes as $note)
                                <div class="card comment-bubble comment-internal mb-3">
                                    <div class="card-body py-2 px-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="fw-semibold small"><i class="bi bi-lock-fill text-warning me-1"></i>{{ $note->user->name }}</span>
                                            <span class="text-muted" style="font-size:.72rem;">{{ $note->created_at->diffForHumans() }}</span>
                                        </div>
                                        <div style="font-size:.875rem; white-space:pre-wrap;">{!! preg_replace('/@([\w\s]{2,40})/', '<span class="badge bg-warning-subtle text-warning-emphasis">@$1</span>', e($note->body)) !!}</div>
                                        @if($note->attachments->count())
                                        <div class="mt-2 d-flex flex-wrap gap-1">
                                            @foreach($note->attachments as $att)
                                            <a href="{{ route('attachments.download', $att) }}"
                                               class="badge bg-light text-secondary border text-decoration-none d-flex align-items-center gap-1"
                                               style="font-weight:400; font-size:.75rem;">
                                                <i class="bi bi-paperclip"></i> {{ $att->original_name }}
                                            </a>
                                            @endforeach
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted text-center py-3 small">Belum ada catatan internal</p>
                            @endforelse
                        </div>

                        @if($isActive)
                        <form method="POST" action="{{ route('comments.store', $ticket) }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="is_internal" value="1">
                            <textarea name="body" class="form-control mb-2" rows="3"
                                      placeholder="Catatan internal (tidak terlihat oleh pelapor)... Gunakan @nama untuk mention" required></textarea>
                            <div class="d-flex align-items-center gap-2">
                                <label class="btn btn-outline-secondary btn-sm mb-0" style="cursor:pointer;">
                                    <i class="bi bi-paperclip me-1"></i> Lampiran
                                    <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.zip" class="d-none" id="internalFiles">
                                </label>
                                <div id="internalFilePreview" class="d-flex flex-wrap gap-1 flex-grow-1 small text-muted"></div>
                                <button type="submit" class="btn btn-warning btn-sm ms-auto">
                                    <i class="bi bi-lock me-1"></i> Simpan Catatan
                                </button>
                            </div>
                        </form>
                        @endif
                    </div>
                    @endif

                    <!-- Timeline -->
                    <div class="tab-pane fade" id="tab-timeline">
                        <div class="timeline mt-2">
                            @foreach($ticket->statusLogs as $log)
                                <div class="timeline-item">
                                    <div class="timeline-icon bg-{{ \App\Models\Ticket::STATUS_COLORS[$log->to_status] ?? 'secondary' }}-subtle
                                                              text-{{ \App\Models\Ticket::STATUS_COLORS[$log->to_status] ?? 'secondary' }}">
                                        <i class="bi bi-circle-fill" style="font-size:.4rem;"></i>
                                    </div>
                                    <div class="timeline-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                @if($log->from_status)
                                                    <span class="badge bg-secondary-subtle text-secondary small">{{ \App\Models\Ticket::STATUS_LABELS[$log->from_status] ?? $log->from_status }}</span>
                                                    <i class="bi bi-arrow-right mx-1 text-muted small"></i>
                                                @endif
                                                <span class="badge bg-{{ \App\Models\Ticket::STATUS_COLORS[$log->to_status] ?? 'secondary' }}-subtle text-{{ \App\Models\Ticket::STATUS_COLORS[$log->to_status] ?? 'secondary' }} small">
                                                    {{ \App\Models\Ticket::STATUS_LABELS[$log->to_status] ?? $log->to_status }}
                                                </span>
                                            </div>
                                            <span class="text-muted" style="font-size:.72rem;">{{ $log->created_at->isoFormat('D MMM HH:mm') }}</span>
                                        </div>
                                        <div class="small mt-1"><strong>{{ $log->user->name }}</strong>: {{ $log->note }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Sidebar -->
    <div class="col-lg-4">
        <!-- SLA Card -->
        @if($ticket->sla_deadline && $isActive)
        <div class="card border-0 shadow-sm mb-3 border-{{ $slaStatus === 'overdue' ? 'danger' : ($slaStatus === 'warning' ? 'warning' : 'success') }}"
             style="border-width: 2px !important;">
            <div class="card-body text-center py-3">
                <div class="sla-{{ $slaStatus }} sla-countdown" id="sla-countdown">
                    @php
                        $diff = now()->diff($ticket->sla_deadline);
                        $remaining = $diff->days . 'h ' . $diff->h . 'j ' . $diff->i . 'm';
                    @endphp
                    {{ $slaStatus === 'overdue' ? 'TERLAMPAUI' : $remaining }}
                </div>
                <div class="text-muted small mt-1">
                    Deadline: {{ $ticket->sla_deadline->isoFormat('D MMM YYYY, HH:mm') }}
                </div>
                @php
                    $pctVal = min(100, (now()->diffInMinutes($ticket->created_at) / max(1, $ticket->sla_deadline->diffInMinutes($ticket->created_at))) * 100);
                @endphp
                <div class="progress mt-2" style="height:6px;">
                    <div class="progress-bar bg-{{ $slaStatus === 'overdue' ? 'danger' : ($slaStatus === 'warning' ? 'warning' : 'success') }}"
                         style="width:{{ $pctVal }}%"></div>
                </div>
                <div class="text-muted" style="font-size:.72rem; margin-top:.3rem;">
                    {{ round($pctVal) }}% waktu terpakai
                </div>
            </div>
        </div>
        @endif

        <!-- Details Card -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold">Detail Tiket</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr><td class="text-muted fw-semibold" style="width:40%">Pelapor</td>
                        <td>{{ $ticket->user->name }}</td></tr>
                    <tr><td class="text-muted fw-semibold">Departemen</td>
                        <td>{{ $ticket->user->department ?? '—' }}</td></tr>
                    <tr><td class="text-muted fw-semibold">Teknisi</td>
                        <td>{{ $ticket->assignee?->name ?? '<span class="text-muted">Belum di-assign</span>' }}</td></tr>
                    <tr><td class="text-muted fw-semibold">Prioritas</td>
                        <td><span class="badge priority-{{ $ticket->priority }}">{{ ucfirst($ticket->priority) }}</span>
                            @if($user->isSupervisor() && $isActive)
                                <button class="btn btn-link btn-sm p-0 ms-1 text-muted" data-bs-toggle="modal" data-bs-target="#priorityModal">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            @endif
                        </td></tr>
                    <tr><td class="text-muted fw-semibold">Kategori</td>
                        <td>{{ $ticket->category?->name ?? '—' }}</td></tr>
                    @if($ticket->subcategory)
                    <tr><td class="text-muted fw-semibold">Subkategori</td>
                        <td>{{ $ticket->subcategory->name }}</td></tr>
                    @endif
                    <tr><td class="text-muted fw-semibold">Dibuat</td>
                        <td>{{ $ticket->created_at->isoFormat('D MMM YYYY') }}</td></tr>
                    @if($ticket->resolved_at)
                    <tr><td class="text-muted fw-semibold">Diselesaikan</td>
                        <td>{{ $ticket->resolved_at->isoFormat('D MMM YYYY') }}</td></tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- Rating -->
        @if($ticket->rating)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h6 class="fw-bold mb-2"><i class="bi bi-star-fill text-warning me-1"></i>Rating Pengguna</h6>
                <div class="text-warning mb-1">
                    @for($s=1; $s<=5; $s++)
                        <i class="bi bi-star{{ $s <= $ticket->rating->rating ? '-fill' : '' }}"></i>
                    @endfor
                    <span class="text-dark ms-1 fw-bold">{{ $ticket->rating->rating }}/5</span>
                </div>
                @if($ticket->rating->comment)
                    <p class="small text-muted mb-0">{{ $ticket->rating->comment }}</p>
                @endif
            </div>
        </div>
        @endif

        <!-- Attachments -->
        @if($ticket->attachments->count() > 0)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-paperclip me-2"></i>Lampiran ({{ $ticket->attachments->count() }})</h6>
            </div>
            <div class="card-body p-0">
                @foreach($ticket->attachments as $att)
                    <div class="d-flex align-items-center gap-2 px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <i class="bi bi-file-earmark text-muted"></i>
                        <div class="flex-grow-1 small text-truncate">{{ $att->original_name }}</div>
                        <span class="text-muted small">{{ $att->size_formatted }}</span>
                        <a href="{{ route('attachments.download', $att) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-download"></i>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Knowledge Base Articles -->
        @php $kbArticles = $ticket->kbArticles; @endphp
        @if($kbArticles->count() || ($user->isTeknisi() || $user->isSupervisor()))
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-journal-richtext me-2 text-success"></i>Knowledge Base</h6>
            </div>
            <div class="card-body p-0">
                @forelse($kbArticles as $kb)
                <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom">
                    <i class="bi bi-file-text text-success flex-shrink-0"></i>
                    <a href="{{ route('knowledge-base.show', $kb) }}" target="_blank"
                       class="flex-grow-1 small text-decoration-none text-dark fw-semibold">
                        {{ $kb->title }}
                    </a>
                    @if($user->isTeknisi() || $user->isSupervisor())
                    <form method="POST" action="{{ route('tickets.kb-detach', [$ticket, $kb]) }}">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" title="Lepas"><i class="bi bi-x"></i></button>
                    </form>
                    @endif
                </div>
                @empty
                <p class="text-muted small text-center py-3 mb-0">Belum ada artikel dilampirkan.</p>
                @endforelse

                @if($user->isTeknisi() || $user->isSupervisor())
                <div class="px-3 py-2">
                    <form method="POST" action="{{ route('tickets.kb-attach', $ticket) }}" class="d-flex gap-2">
                        @csrf
                        <input type="text" id="kbAttachSearch" class="form-control form-control-sm"
                               placeholder="Cari artikel KB..." autocomplete="off">
                        <button type="submit" id="kbAttachBtn" class="btn btn-outline-success btn-sm" disabled>
                            <i class="bi bi-link"></i>
                        </button>
                        <input type="hidden" name="kb_article_id" id="kbAttachId">
                    </form>
                    <div id="kbAttachSuggestions" class="list-group shadow-sm mt-1" style="display:none; position:absolute; z-index:100; width:calc(100% - 4rem);"></div>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Upload Attachment (Teknisi) -->
        @if(($user->isTeknisi() || $user->isSupervisor()) && $isActive)
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-upload me-2"></i>Tambah Lampiran</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('attachments.store', $ticket) }}" enctype="multipart/form-data">
                    @csrf
                    <input type="file" name="attachments[]" multiple class="form-control form-control-sm mb-2"
                           accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx">
                    <button type="submit" class="btn btn-outline-primary btn-sm w-100">Upload</button>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Modals -->
@if($user->isSupervisor() && $isActive)
<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('tickets.status', $ticket) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="cancelled">
                <div class="modal-header">
                    <h5 class="modal-title">Batalkan Tiket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Apakah Anda yakin ingin membatalkan tiket <strong>{{ $ticket->ticket_number }}</strong>?</p>
                    <div class="mb-3">
                        <label class="form-label">Alasan pembatalan <span class="text-danger">*</span></label>
                        <textarea name="note" class="form-control" rows="3" required
                                  placeholder="Jelaskan alasan pembatalan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Batalkan Tiket</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Priority Modal -->
<div class="modal fade" id="priorityModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST" action="{{ route('tickets.priority', $ticket) }}">
                @csrf @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Prioritas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <select name="priority" class="form-select">
                        @foreach(['kritis', 'tinggi', 'sedang', 'rendah'] as $p)
                            <option value="{{ $p }}" {{ $ticket->priority === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
function setupFilePreview(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    if (!input || !preview) return;
    input.addEventListener('change', () => {
        preview.innerHTML = '';
        Array.from(input.files).forEach(f => {
            const chip = document.createElement('span');
            chip.className = 'badge bg-light text-secondary border d-flex align-items-center gap-1';
            chip.style.fontWeight = '400';
            chip.innerHTML = `<i class="bi bi-paperclip"></i> ${f.name}`;
            preview.appendChild(chip);
        });
    });
}
setupFilePreview('commentFiles', 'filePreview');
setupFilePreview('internalFiles', 'internalFilePreview');

// KB attach search
const kbSearchInput = document.getElementById('kbAttachSearch');
const kbSuggestions = document.getElementById('kbAttachSuggestions');
const kbAttachIdInput = document.getElementById('kbAttachId');
const kbAttachBtn = document.getElementById('kbAttachBtn');

if (kbSearchInput) {
    let kbTimer;
    kbSearchInput.addEventListener('input', function() {
        clearTimeout(kbTimer);
        const q = this.value.trim();
        if (q.length < 2) { kbSuggestions.style.display = 'none'; return; }
        kbTimer = setTimeout(() => {
            fetch(`/knowledge-base/search?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(data => {
                    kbSuggestions.innerHTML = '';
                    if (!data.length) { kbSuggestions.style.display = 'none'; return; }
                    data.forEach(a => {
                        const item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'list-group-item list-group-item-action small py-2';
                        item.textContent = a.title;
                        item.addEventListener('click', () => {
                            kbSearchInput.value = a.title;
                            kbAttachIdInput.value = a.id;
                            kbAttachBtn.disabled = false;
                            kbSuggestions.style.display = 'none';
                        });
                        kbSuggestions.appendChild(item);
                    });
                    kbSuggestions.style.display = 'block';
                });
        }, 300);
    });
    document.addEventListener('click', e => {
        if (!kbSearchInput.contains(e.target) && !kbSuggestions.contains(e.target)) {
            kbSuggestions.style.display = 'none';
        }
    });
}
</script>
@endpush
@endsection
