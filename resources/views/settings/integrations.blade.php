@extends('layouts.app')
@section('title', 'Integrasi')
@section('breadcrumb')
    <li class="breadcrumb-item active">Integrasi</li>
@endsection
@section('content')
<div class="mb-4">
    <h1 class="page-title">Integrasi</h1>
    <p class="page-subtitle">Hubungkan Helpdesk IT dengan Telegram, WhatsApp, dan Active Directory.</p>
</div>

<form method="POST" action="{{ route('settings.integrations.update') }}">
@csrf @method('PATCH')

<!-- Telegram -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold"><i class="bi bi-telegram me-2" style="color:#229ED9;"></i>Telegram Bot</h6>
        <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" name="telegram_enabled" value="1" id="tgEnabled"
                   {{ ($settings['telegram_enabled'] ?? '0') === '1' ? 'checked' : '' }}>
            <label class="form-check-label small fw-semibold" for="tgEnabled">Aktif</label>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Bot Token</label>
                <input type="password" name="telegram_bot_token" class="form-control"
                       value="{{ $settings['telegram_bot_token'] ? '••••••••' : '' }}"
                       placeholder="123456789:AABBCCDDEEFFaabbccddeeff...">
                <div class="form-text">Dari @BotFather di Telegram.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Chat ID / Group ID</label>
                <input type="text" name="telegram_chat_id" class="form-control"
                       value="{{ $settings['telegram_chat_id'] ?? '' }}"
                       placeholder="-100123456789">
                <div class="form-text">Gunakan @userinfobot untuk mendapatkan ID grup.</div>
            </div>
        </div>
        <div class="d-flex gap-2 mt-3">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="testIntegration('telegram')">
                <i class="bi bi-wifi me-1"></i> Test Koneksi
            </button>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="sendTest('telegram')">
                <i class="bi bi-send me-1"></i> Kirim Pesan Test
            </button>
            <span id="tgResult" class="small align-self-center ms-2"></span>
        </div>
    </div>
</div>

<!-- WhatsApp -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold"><i class="bi bi-whatsapp me-2 text-success"></i>WhatsApp (Fonnte)</h6>
        <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" name="whatsapp_enabled" value="1" id="waEnabled"
                   {{ ($settings['whatsapp_enabled'] ?? '0') === '1' ? 'checked' : '' }}>
            <label class="form-check-label small fw-semibold" for="waEnabled">Aktif</label>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">API Key Fonnte</label>
                <input type="password" name="whatsapp_api_key" class="form-control"
                       value="{{ $settings['whatsapp_api_key'] ? '••••••••' : '' }}"
                       placeholder="Token dari fonnte.com">
                <div class="form-text">Daftar di <a href="https://fonnte.com" target="_blank">fonnte.com</a> untuk mendapatkan API key.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Nomor/Grup Tujuan</label>
                <input type="text" name="whatsapp_target" class="form-control"
                       value="{{ $settings['whatsapp_target'] ?? '' }}"
                       placeholder="628123456789 atau grup ID">
                <div class="form-text">Format internasional tanpa + (contoh: 6281234567890).</div>
            </div>
        </div>
        <div class="d-flex gap-2 mt-3">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="testIntegration('whatsapp')">
                <i class="bi bi-wifi me-1"></i> Test Koneksi
            </button>
            <button type="button" class="btn btn-outline-success btn-sm" onclick="sendTest('whatsapp')">
                <i class="bi bi-send me-1"></i> Kirim Pesan Test
            </button>
            <span id="waResult" class="small align-self-center ms-2"></span>
        </div>
    </div>
</div>

<!-- LDAP / Active Directory -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold"><i class="bi bi-building me-2 text-primary"></i>SSO / Active Directory (LDAP)</h6>
        <div class="d-flex align-items-center gap-3">
            @if(!$ldapExtLoaded)
            <span class="badge bg-danger-subtle text-danger small"><i class="bi bi-exclamation-triangle me-1"></i>ext-ldap tidak aktif</span>
            @endif
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" name="ldap_enabled" value="1" id="ldapEnabled"
                       {{ ($settings['ldap_enabled'] ?? '0') === '1' ? 'checked' : '' }}
                       {{ !$ldapExtLoaded ? 'disabled' : '' }}>
                <label class="form-check-label small fw-semibold" for="ldapEnabled">Aktif</label>
            </div>
        </div>
    </div>
    <div class="card-body">
        @if(!$ldapExtLoaded)
        <div class="alert alert-warning small mb-3">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            PHP extension <code>ext-ldap</code> tidak ditemukan. Aktifkan dengan menghapus <code>;</code> di depan <code>extension=ldap</code> di <code>php.ini</code>, lalu restart Apache.
        </div>
        @endif
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">LDAP Host</label>
                <input type="text" name="ldap_host" class="form-control"
                       value="{{ $settings['ldap_host'] ?? '' }}" placeholder="192.168.1.10">
            </div>
            <div class="col-md-2">
                <label class="form-label">Port</label>
                <input type="number" name="ldap_port" class="form-control"
                       value="{{ $settings['ldap_port'] ?? 389 }}" min="1" max="65535">
            </div>
            <div class="col-md-6">
                <label class="form-label">Base DN</label>
                <input type="text" name="ldap_base_dn" class="form-control"
                       value="{{ $settings['ldap_base_dn'] ?? '' }}" placeholder="dc=perusahaan,dc=local">
            </div>
            <div class="col-md-6">
                <label class="form-label">Bind DN (Service Account)</label>
                <input type="text" name="ldap_bind_dn" class="form-control"
                       value="{{ $settings['ldap_bind_dn'] ?? '' }}" placeholder="cn=svcaccount,ou=Service,dc=perusahaan,dc=local">
            </div>
            <div class="col-md-6">
                <label class="form-label">Bind Password</label>
                <input type="password" name="ldap_bind_password" class="form-control"
                       value="{{ $settings['ldap_bind_password'] ? '••••••••' : '' }}" placeholder="Password service account">
            </div>
            <div class="col-md-6">
                <label class="form-label">User Filter</label>
                <input type="text" name="ldap_user_filter" class="form-control"
                       value="{{ $settings['ldap_user_filter'] ?? '(sAMAccountName={username})' }}"
                       placeholder="(sAMAccountName={username})">
                <div class="form-text"><code>{username}</code> akan diganti nilai login.</div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Atribut Email</label>
                <input type="text" name="ldap_attr_email" class="form-control"
                       value="{{ $settings['ldap_attr_email'] ?? 'mail' }}" placeholder="mail">
            </div>
            <div class="col-md-3">
                <label class="form-label">Atribut Nama</label>
                <input type="text" name="ldap_attr_name" class="form-control"
                       value="{{ $settings['ldap_attr_name'] ?? 'displayName' }}" placeholder="displayName">
            </div>
            <div class="col-md-3">
                <label class="form-label">Role Default</label>
                <select name="ldap_default_role" class="form-select">
                    @foreach(['user' => 'User', 'teknisi' => 'Teknisi', 'supervisor' => 'Supervisor'] as $r => $l)
                    <option value="{{ $r }}" {{ ($settings['ldap_default_role'] ?? 'user') === $r ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <div class="form-text">Role untuk akun baru yang belum ada di sistem.</div>
            </div>
        </div>
        <div class="d-flex gap-2 mt-3">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="testIntegration('ldap')">
                <i class="bi bi-plug me-1"></i> Test Koneksi LDAP
            </button>
            <span id="ldapResult" class="small align-self-center ms-2"></span>
        </div>
    </div>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-save me-1"></i> Simpan Semua Pengaturan
    </button>
</div>
</form>
@endsection

@push('scripts')
<script>
function testIntegration(channel) {
    const btn = event.target;
    const resultEl = document.getElementById(channel.replace('whatsapp','wa').replace('telegram','tg').replace('ldap','ldap') + 'Result');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    fetch(`/settings/integrations/test-${channel}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(d => {
            resultEl.className = 'small align-self-center ms-2 ' + (d.ok ? 'text-success' : 'text-danger');
            resultEl.innerHTML = (d.ok ? '<i class="bi bi-check-circle-fill"></i>' : '<i class="bi bi-x-circle-fill"></i>') + ' ' + d.message;
        })
        .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="bi bi-' + (channel === 'ldap' ? 'plug' : 'wifi') + ' me-1"></i> Test Koneksi' + (channel !== 'ldap' ? '' : ' LDAP'); });
}
function sendTest(channel) {
    const resultId = channel === 'telegram' ? 'tgResult' : 'waResult';
    const resultEl = document.getElementById(resultId);
    fetch('/settings/integrations/send-test', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: JSON.stringify({ channel })
    }).then(r => r.json()).then(d => {
        resultEl.className = 'small align-self-center ms-2 ' + (d.ok ? 'text-success' : 'text-danger');
        resultEl.innerHTML = (d.ok ? '✅' : '❌') + ' ' + d.message;
    });
}
</script>
@endpush
