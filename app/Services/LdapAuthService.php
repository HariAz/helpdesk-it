<?php

namespace App\Services;

use App\Models\IntegrationSetting;
use App\Models\User;

class LdapAuthService
{
    private array $config;

    public function __construct()
    {
        $this->config = IntegrationSetting::getMany([
            'ldap_host', 'ldap_port', 'ldap_base_dn',
            'ldap_bind_dn', 'ldap_bind_password',
            'ldap_user_filter', 'ldap_attr_email', 'ldap_attr_name',
            'ldap_default_role',
        ]);
    }

    public function isEnabled(): bool
    {
        return IntegrationSetting::getBool('ldap_enabled')
            && !empty($this->config['ldap_host'])
            && !empty($this->config['ldap_base_dn'])
            && extension_loaded('ldap');
    }

    public function authenticate(string $username, string $password): User|false
    {
        if (!$this->isEnabled()) return false;

        $host = $this->config['ldap_host'];
        $port = (int)($this->config['ldap_port'] ?: 389);

        $conn = @ldap_connect("ldap://{$host}:{$port}");
        if (!$conn) return false;

        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);

        // Service account bind (to search for user DN)
        $bindDn  = $this->config['ldap_bind_dn'] ?: null;
        $bindPwd = $this->config['ldap_bind_password'] ?: null;
        $bound   = $bindDn ? @ldap_bind($conn, $bindDn, $bindPwd) : @ldap_bind($conn);
        if (!$bound) return false;

        // Find user entry
        $baseDn = $this->config['ldap_base_dn'];
        $filter = str_replace('{username}', ldap_escape($username, '', LDAP_ESCAPE_FILTER),
            $this->config['ldap_user_filter'] ?: '(sAMAccountName={username})');
        $emailAttr = $this->config['ldap_attr_email'] ?: 'mail';
        $nameAttr  = $this->config['ldap_attr_name']  ?: 'displayName';

        $result = @ldap_search($conn, $baseDn, $filter, [$emailAttr, $nameAttr, 'dn']);
        if (!$result) return false;

        $entries = ldap_get_entries($conn, $result);
        if ($entries['count'] === 0) return false;

        $userDn   = $entries[0]['dn'];
        $userEmail = strtolower($entries[0][$emailAttr][0] ?? '');
        $userName  = $entries[0][$nameAttr][0] ?? $username;

        // Bind as the user to verify password
        if (!@ldap_bind($conn, $userDn, $password)) return false;
        ldap_unbind($conn);

        if (!$userEmail) return false;

        // Find or provision local user
        $user = User::firstOrCreate(
            ['email' => $userEmail],
            [
                'name'      => $userName,
                'password'  => bcrypt(\Illuminate\Support\Str::random(32)),
                'role'      => $this->config['ldap_default_role'] ?: 'user',
                'is_active' => true,
            ]
        );

        // Update name if changed in LDAP
        if ($user->name !== $userName) {
            $user->update(['name' => $userName]);
        }

        return $user->is_active ? $user : false;
    }

    public function testConnection(): array
    {
        if (!extension_loaded('ldap')) {
            return ['ok' => false, 'message' => 'PHP ext-ldap tidak aktif. Aktifkan extension=ldap di php.ini.'];
        }
        if (!$this->config['ldap_host']) {
            return ['ok' => false, 'message' => 'LDAP host belum dikonfigurasi.'];
        }

        $host = $this->config['ldap_host'];
        $port = (int)($this->config['ldap_port'] ?: 389);
        $conn = @ldap_connect("ldap://{$host}:{$port}");
        if (!$conn) return ['ok' => false, 'message' => "Tidak bisa connect ke {$host}:{$port}"];

        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_NETWORK_TIMEOUT, 5);

        $bindDn  = $this->config['ldap_bind_dn'] ?: null;
        $bindPwd = $this->config['ldap_bind_password'] ?: null;
        $bound   = $bindDn ? @ldap_bind($conn, $bindDn, $bindPwd) : @ldap_bind($conn);
        ldap_unbind($conn);

        return $bound
            ? ['ok' => true,  'message' => "Berhasil terhubung ke {$host}:{$port}"]
            : ['ok' => false, 'message' => "Gagal bind ke {$host}:{$port} — periksa bind DN/password"];
    }
}
