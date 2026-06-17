<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rating Tiket — Helpdesk IT</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background: #f1f5f9; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
        .rating-card { max-width: 520px; width: 100%; background: #fff; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,.12); overflow: hidden; }
        .rating-header { background: linear-gradient(135deg, #0f172a, #1a56db); padding: 2rem; color: #fff; text-align: center; }
        .star-rating { display: flex; gap: .5rem; justify-content: center; flex-direction: row-reverse; }
        .star-rating input { display: none; }
        .star-rating label { font-size: 2.5rem; color: #cbd5e1; cursor: pointer; transition: color .15s; }
        .star-rating label:hover, .star-rating label:hover ~ label,
        .star-rating input:checked ~ label { color: #f59e0b; }
    </style>
</head>
<body>
<div class="px-3 w-100" style="max-width:540px; margin:auto;">
    <div class="rating-card">
        <div class="rating-header">
            <i class="bi bi-star-fill fs-1 mb-2 d-block text-warning"></i>
            <h4 class="fw-bold mb-1">Berikan Penilaian Anda</h4>
            <p class="mb-0 opacity-75 small">Bantu kami meningkatkan kualitas layanan IT</p>
        </div>
        <div class="p-4">
            <!-- Ticket Info -->
            <div class="bg-light rounded-3 p-3 mb-4">
                <div class="small text-muted mb-1">Tiket yang diselesaikan:</div>
                <div class="fw-bold">{{ $ratingToken->ticket->ticket_number }}</div>
                <div class="text-secondary small">{{ $ratingToken->ticket->title }}</div>
                @if($ratingToken->ticket->assignee)
                    <div class="text-muted small mt-1"><i class="bi bi-tools me-1"></i>Ditangani oleh: <strong>{{ $ratingToken->ticket->assignee->name }}</strong></div>
                @endif
            </div>

            <form method="POST" action="{{ route('rating.store', $ratingToken->token) }}">
                @csrf
                <div class="mb-4 text-center">
                    <label class="form-label fw-bold">Seberapa puas Anda dengan penanganan tiket ini?</label>
                    <div class="star-rating my-3">
                        @for($s=5; $s>=1; $s--)
                            <input type="radio" id="star{{ $s }}" name="rating" value="{{ $s }}" required>
                            <label for="star{{ $s }}" title="{{ $s }} bintang"><i class="bi bi-star-fill"></i></label>
                        @endfor
                    </div>
                    <div class="d-flex justify-content-between text-muted small px-4">
                        <span>Sangat Buruk</span>
                        <span>Sangat Baik</span>
                    </div>
                    @error('rating')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Komentar (opsional)</label>
                    <textarea name="comment" class="form-control" rows="4"
                              placeholder="Ceritakan pengalaman Anda dengan layanan helpdesk ini..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="bi bi-send me-1"></i> Kirim Penilaian
                </button>
            </form>
        </div>
    </div>
    <p class="text-center text-secondary small mt-3">&copy; {{ date('Y') }} Helpdesk IT System</p>
</div>
</body>
</html>
