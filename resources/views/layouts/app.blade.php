<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Helpdesk IT</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        :root {
            --bs-primary: #1a56db;
            --sidebar-width: 260px;
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --accent: #f97316;
        }
        body { background: #f1f5f9; font-family: 'Segoe UI', sans-serif; }

        /* Sidebar */
        #sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: var(--sidebar-bg);
            position: fixed;
            top: 0; left: 0;
            z-index: 1040;
            transition: transform .25s ease;
            display: flex;
            flex-direction: column;
        }
        #sidebar .sidebar-brand {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        #sidebar .sidebar-brand .brand-icon {
            width: 38px; height: 38px;
            background: #1a56db;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; color: white;
        }
        #sidebar .sidebar-brand h6 { color: #fff; font-weight: 700; margin: 0; font-size: .9rem; }
        #sidebar .sidebar-brand small { color: #94a3b8; font-size: .75rem; }

        #sidebar .nav-section {
            padding: .75rem 1rem .25rem;
            font-size: .65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #475569;
        }
        #sidebar .nav-link {
            padding: .55rem 1.25rem;
            color: #94a3b8;
            border-radius: 8px;
            margin: 1px .5rem;
            font-size: .875rem;
            display: flex; align-items: center; gap: .6rem;
            transition: all .15s;
            position: relative;
        }
        #sidebar .nav-link:hover { background: var(--sidebar-hover); color: #e2e8f0; }
        #sidebar .nav-link.active { background: #1a56db; color: #fff; }
        #sidebar .nav-link .bi { font-size: 1rem; flex-shrink: 0; }
        #sidebar .badge-sidebar {
            margin-left: auto;
            font-size: .65rem;
            padding: .2em .5em;
        }
        #sidebar .sidebar-user {
            margin-top: auto;
            padding: 1rem 1.25rem;
            border-top: 1px solid rgba(255,255,255,.08);
        }
        #sidebar .sidebar-user .avatar {
            width: 36px; height: 36px;
            background: #1a56db;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 700; font-size: .8rem;
            flex-shrink: 0;
        }
        #sidebar .sidebar-user .user-info { overflow: hidden; }
        #sidebar .sidebar-user .user-name { color: #e2e8f0; font-size: .85rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        #sidebar .sidebar-user .user-role { color: #64748b; font-size: .72rem; }

        /* Main wrapper */
        #main-wrapper { margin-left: var(--sidebar-width); transition: margin .25s ease; }

        /* Topbar */
        #topbar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: .75rem 1.5rem;
            position: sticky; top: 0; z-index: 1030;
        }
        #topbar .breadcrumb { margin: 0; font-size: .8rem; }
        #topbar .breadcrumb-item + .breadcrumb-item::before { color: #94a3b8; }

        /* Page content */
        .page-content { padding: 1.5rem; }
        .page-title { font-size: 1.4rem; font-weight: 700; color: #0f172a; margin-bottom: .25rem; }
        .page-subtitle { color: #64748b; font-size: .875rem; }

        /* Cards */
        .stat-card { border: none; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        .stat-card .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
        .stat-value { font-size: 1.8rem; font-weight: 700; line-height: 1; }
        .stat-label { font-size: .78rem; color: #64748b; margin-top: .25rem; }

        /* SLA indicator */
        .sla-ok { color: #16a34a; }
        .sla-warning { color: #d97706; }
        .sla-overdue { color: #dc2626; }
        .sla-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: .3rem; }
        .sla-dot-ok { background: #16a34a; }
        .sla-dot-warning { background: #d97706; }
        .sla-dot-overdue { background: #dc2626; }

        /* Priority badges */
        .priority-kritis { background: #fee2e2; color: #991b1b; border-radius: 20px; }
        .priority-tinggi { background: #fef3c7; color: #92400e; border-radius: 20px; }
        .priority-sedang { background: #dbeafe; color: #1e40af; border-radius: 20px; }
        .priority-rendah { background: #f1f5f9; color: #475569; border-radius: 20px; }

        /* Timeline */
        .timeline { position: relative; padding-left: 2.5rem; }
        .timeline::before { content: ''; position: absolute; left: .95rem; top: 0; bottom: 0; width: 2px; background: #e2e8f0; }
        .timeline-item { position: relative; padding-bottom: 1.25rem; }
        .timeline-icon { position: absolute; left: -2.5rem; width: 1.9rem; height: 1.9rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .75rem; border: 2px solid #fff; box-shadow: 0 0 0 1px #e2e8f0; }
        .timeline-body { background: #fff; border-radius: 10px; padding: .75rem 1rem; box-shadow: 0 1px 3px rgba(0,0,0,.05); }

        /* Comment box */
        .comment-internal { border-left: 3px solid #f97316 !important; background: #fff7ed !important; }
        .comment-public { border-left: 3px solid #3b82f6 !important; }

        /* Overlay */
        #sidebar-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,.5);
            z-index: 1039;
        }

        /* Responsive */
        @media (max-width: 991px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.show { transform: translateX(0); }
            #sidebar-overlay.show { display: block; }
            #main-wrapper { margin-left: 0; }
        }

        /* Tables */
        .table thead th { font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #64748b; background: #f8fafc; }
        .table td { vertical-align: middle; font-size: .875rem; }

        /* Form labels */
        .form-label { font-weight: 600; font-size: .8rem; color: #374151; }

        /* Ticket number font */
        .ticket-number { font-family: monospace; font-size: .8rem; background: #f1f5f9; padding: .15rem .4rem; border-radius: 4px; }
    </style>
    @stack('styles')
</head>
<body>

<div id="sidebar-overlay"></div>

<!-- Sidebar -->
<nav id="sidebar">
    <div class="sidebar-brand d-flex align-items-center gap-2">
        <div class="brand-icon"><i class="bi bi-headset"></i></div>
        <div>
            <h6>Helpdesk IT</h6>
            <small>Sistem Tiket Layanan</small>
        </div>
    </div>

    <div class="py-2 flex-grow-1 overflow-auto">
        <div class="nav-section">Menu Utama</div>
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="{{ route('tickets.index') }}" class="nav-link {{ request()->routeIs('tickets.*') && !request()->routeIs('tickets.trash') ? 'active' : '' }}">
            <i class="bi bi-ticket-detailed"></i> Tiket
            @if(auth()->user()->isTeknisi())
                @php $unassignedCount = \App\Models\Ticket::whereNull('assigned_to')->whereNotIn('status', ['closed','cancelled'])->count(); @endphp
                @if($unassignedCount > 0)
                    <span class="badge bg-warning text-dark badge-sidebar">{{ $unassignedCount }}</span>
                @endif
            @endif
            @if(auth()->user()->isSupervisor())
                @php $escalatedCount = \App\Models\Ticket::where('is_escalated', true)->whereNotIn('status', ['closed','cancelled'])->count(); @endphp
                @if($escalatedCount > 0)
                    <span class="badge bg-danger badge-sidebar">{{ $escalatedCount }}</span>
                @endif
            @endif
            @if(auth()->user()->isUser())
                @php $newReplyCount = \App\Models\Ticket::where('user_id', auth()->id())->where('status', 'pending_user')->count(); @endphp
                @if($newReplyCount > 0)
                    <span class="badge bg-primary badge-sidebar">{{ $newReplyCount }}</span>
                @endif
            @endif
        </a>

        @if(auth()->user()->isUser())
            <a href="{{ route('tickets.create') }}" class="nav-link {{ request()->routeIs('tickets.create') ? 'active' : '' }}">
                <i class="bi bi-plus-circle"></i> Buat Tiket
            </a>
        @endif

        @if(auth()->user()->isSupervisor())
            <div class="nav-section">Manajemen</div>
            <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Pengguna
            </a>
            <a href="{{ route('categories.index') }}" class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                <i class="bi bi-tags"></i> Kategori
            </a>
            <div class="nav-section">Laporan</div>
            <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-line"></i> Laporan
            </a>
            <a href="{{ route('activity-logs.index') }}" class="nav-link {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i> Log Aktivitas
            </a>
            <a href="{{ route('tickets.trash') }}" class="nav-link {{ request()->routeIs('tickets.trash') ? 'active' : '' }}">
                <i class="bi bi-trash3"></i> Sampah
            </a>
        @endif
    </div>

    <div class="sidebar-user">
        <div class="d-flex align-items-center gap-2">
            <div class="avatar">{{ auth()->user()->initials }}</div>
            <div class="user-info flex-grow-1">
                <div class="user-name">{{ auth()->user()->name }}</div>
                <div class="user-role">{{ ucfirst(auth()->user()->role) }}</div>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-sm btn-link text-secondary p-0" title="Logout">
                    <i class="bi bi-box-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>
</nav>

<!-- Main wrapper -->
<div id="main-wrapper">
    <!-- Topbar -->
    <div id="topbar" class="d-flex align-items-center gap-3">
        <button class="btn btn-sm btn-light d-lg-none" id="sidebar-toggle">
            <i class="bi bi-list fs-5"></i>
        </button>
        <nav aria-label="breadcrumb" class="flex-grow-1">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Home</a></li>
                @yield('breadcrumb')
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2 text-muted small">
            <i class="bi bi-calendar3"></i>
            {{ now()->isoFormat('dddd, D MMMM YYYY') }}
        </div>
    </div>

    <!-- Content -->
    <main class="page-content">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Sidebar toggle (mobile)
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const toggle = document.getElementById('sidebar-toggle');

    if (toggle) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });
    }
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
    });

    // Auto-dismiss alerts
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(el => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            bsAlert.close();
        });
    }, 5000);
</script>
@stack('scripts')
</body>
</html>
