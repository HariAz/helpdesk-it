<?php

namespace App\Http\Controllers;

use App\Models\IntegrationSetting;
use App\Services\TelegramService;
use App\Services\WhatsAppService;
use App\Services\LdapAuthService;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    public function index()
    {
        $keys = [
            'telegram_enabled', 'telegram_bot_token', 'telegram_chat_id',
            'whatsapp_enabled', 'whatsapp_api_key', 'whatsapp_target',
            'ldap_enabled', 'ldap_host', 'ldap_port', 'ldap_base_dn',
            'ldap_bind_dn', 'ldap_bind_password',
            'ldap_user_filter', 'ldap_attr_email', 'ldap_attr_name', 'ldap_default_role',
        ];
        $settings = IntegrationSetting::getMany($keys);
        $ldapExtLoaded = extension_loaded('ldap');
        return view('settings.integrations', compact('settings', 'ldapExtLoaded'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'telegram_enabled'    => 'boolean',
            'telegram_bot_token'  => 'nullable|string|max:200',
            'telegram_chat_id'    => 'nullable|string|max:100',
            'whatsapp_enabled'    => 'boolean',
            'whatsapp_api_key'    => 'nullable|string|max:200',
            'whatsapp_target'     => 'nullable|string|max:100',
            'ldap_enabled'        => 'boolean',
            'ldap_host'           => 'nullable|string|max:255',
            'ldap_port'           => 'nullable|integer|min:1|max:65535',
            'ldap_base_dn'        => 'nullable|string|max:500',
            'ldap_bind_dn'        => 'nullable|string|max:500',
            'ldap_bind_password'  => 'nullable|string|max:255',
            'ldap_user_filter'    => 'nullable|string|max:255',
            'ldap_attr_email'     => 'nullable|string|max:80',
            'ldap_attr_name'      => 'nullable|string|max:80',
            'ldap_default_role'   => 'nullable|in:user,teknisi,supervisor',
        ]);

        $encrypted = ['telegram_bot_token', 'whatsapp_api_key', 'ldap_bind_password'];
        $booleans  = ['telegram_enabled', 'whatsapp_enabled', 'ldap_enabled'];

        foreach ($data as $key => $value) {
            if (in_array($key, $booleans)) {
                IntegrationSetting::set($key, $request->boolean($key) ? '1' : '0');
            } elseif ($value !== null) {
                IntegrationSetting::set($key, $value, in_array($key, $encrypted));
            }
        }

        return back()->with('success', 'Pengaturan integrasi disimpan.');
    }

    public function testTelegram()
    {
        $result = app(TelegramService::class)->testConnection();
        return response()->json($result);
    }

    public function testWhatsApp()
    {
        $result = app(WhatsAppService::class)->testConnection();
        return response()->json($result);
    }

    public function testLdap()
    {
        $result = app(LdapAuthService::class)->testConnection();
        return response()->json($result);
    }

    public function sendTestMessage(Request $request)
    {
        $channel = $request->input('channel');
        $msg = '🔧 Test notifikasi dari Helpdesk IT — ' . now()->format('d/m/Y H:i');

        $result = match($channel) {
            'telegram'  => app(TelegramService::class)->send($msg),
            'whatsapp'  => app(WhatsAppService::class)->send($msg),
            default     => false,
        };

        return response()->json(['ok' => $result, 'message' => $result ? 'Pesan terkirim.' : 'Pengiriman gagal.']);
    }
}
