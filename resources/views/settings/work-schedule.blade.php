@extends('layouts.app')
@section('title', 'Jadwal Kerja')
@section('breadcrumb')
    <li class="breadcrumb-item active">Jadwal Kerja</li>
@endsection
@section('content')
<div class="mb-4">
    <h1 class="page-title">Jadwal Kerja & Jam Operasional</h1>
    <p class="page-subtitle">Pengaturan ini menentukan jam kerja yang digunakan untuk menghitung deadline SLA.</p>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<form method="POST" action="{{ route('settings.work-schedule.update') }}">
@csrf
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-clock me-2 text-primary"></i>Jam Kerja per Hari</h6>
    </div>
    <div class="card-body p-0">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width:40px"></th>
                    <th>Hari</th>
                    <th>Jam Mulai</th>
                    <th>Jam Selesai</th>
                    <th>Total Jam</th>
                </tr>
            </thead>
            <tbody>
                @foreach($schedules as $s)
                <tr class="{{ $s->is_working_day ? '' : 'table-light text-muted' }}">
                    <td class="ps-3">
                        <div class="form-check mb-0">
                            <input type="hidden" name="days[{{ $s->day_of_week }}][is_working_day]" value="0">
                            <input class="form-check-input" type="checkbox"
                                   name="days[{{ $s->day_of_week }}][is_working_day]"
                                   value="1"
                                   id="day{{ $s->day_of_week }}"
                                   {{ $s->is_working_day ? 'checked' : '' }}>
                        </div>
                    </td>
                    <td>
                        <label for="day{{ $s->day_of_week }}" class="fw-semibold mb-0" style="cursor:pointer;">
                            {{ $s->day_name }}
                        </label>
                        @if(!$s->is_working_day)<span class="badge bg-secondary-subtle text-secondary ms-2 small">Libur</span>@endif
                    </td>
                    <td>
                        <input type="time" name="days[{{ $s->day_of_week }}][start_time]"
                               class="form-control form-control-sm" style="width:130px;"
                               value="{{ substr($s->start_time, 0, 5) }}"
                               {{ $s->is_working_day ? '' : 'disabled' }}>
                    </td>
                    <td>
                        <input type="time" name="days[{{ $s->day_of_week }}][end_time]"
                               class="form-control form-control-sm" style="width:130px;"
                               value="{{ substr($s->end_time, 0, 5) }}"
                               {{ $s->is_working_day ? '' : 'disabled' }}>
                    </td>
                    <td class="text-muted small total-hours" data-day="{{ $s->day_of_week }}">
                        @if($s->is_working_day)
                            @php
                                [$sh, $sm] = explode(':', $s->start_time);
                                [$eh, $em] = explode(':', $s->end_time);
                                $diff = ($eh * 60 + $em) - ($sh * 60 + $sm);
                            @endphp
                            {{ floor($diff/60) }} jam {{ $diff%60 > 0 ? $diff%60 . ' menit' : '' }}
                        @else
                            —
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <span class="text-muted small"><i class="bi bi-info-circle me-1"></i>Perubahan berlaku untuk tiket baru.</span>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-1"></i> Simpan Jadwal
        </button>
    </div>
</div>
</form>

<div class="card border-0 shadow-sm mt-3">
    <div class="card-body">
        <h6 class="fw-bold mb-2"><i class="bi bi-lightbulb text-warning me-2"></i>Cara Kerja SLA Jam Kerja</h6>
        <ul class="text-muted small mb-0">
            <li>Tiket dibuat Jumat 16:00 dengan SLA 4 jam → deadline Senin 09:00 (bukan Jumat 20:00)</li>
            <li>Tiket dibuat Sabtu → SLA mulai dihitung Senin pagi</li>
            <li>Hari libur atau di luar jam kerja tidak dihitung sebagai waktu SLA</li>
        </ul>
    </div>
</div>
@endsection
@push('scripts')
<script>
document.querySelectorAll('input[type=checkbox]').forEach(cb => {
    cb.addEventListener('change', function() {
        const row = this.closest('tr');
        row.querySelectorAll('input[type=time]').forEach(t => t.disabled = !this.checked);
    });
});
</script>
@endpush
