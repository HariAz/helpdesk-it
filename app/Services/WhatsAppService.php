<?php

namespace App\Services;

use App\Models\IntegrationSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private ?string $apiKey;
    private ?string $target;

    public function __construct()
    {
        $settings = IntegrationSetting::getMany(['whatsapp_api_key', 'whatsapp_target']);
        $this->apiKey = $settings['whatsapp_api_key'] ?: null;
        $this->target = $settings['whatsapp_target'] ?: null;
    }

    public function isConfigured(): bool
    {
        return IntegrationSetting::getBool('whatsapp_enabled') && $this->apiKey && $this->target;
    }

    public function send(string $message): bool
    {
        if (!$this->isConfigured()) return false;

        try {
            // Fonnte API (https://fonnte.com) — popular Indonesian WA gateway
            $response = Http::timeout(15)
                ->withHeaders(['Authorization' => $this->apiKey])
                ->post('https://api.fonnte.com/send', [
                    'target'  => $this->target,
                    'message' => $message,
                    'countryCode' => '62',
                ]);

            if ($response->json('status') === true || $response->json('status') === 'true') {
                return true;
            }
            Log::warning('WhatsApp send failed', ['response' => $response->json()]);
            return false;
        } catch (\Throwable $e) {
            Log::error('WhatsApp exception: ' . $e->getMessage());
            return false;
        }
    }

    public function testConnection(): array
    {
        if (!$this->apiKey) return ['ok' => false, 'message' => 'API key belum dikonfigurasi.'];
        try {
            $res = Http::timeout(10)
                ->withHeaders(['Authorization' => $this->apiKey])
                ->post('https://api.fonnte.com/validate-token', []);
            $data = $res->json();
            if ($res->successful() && ($data['status'] ?? false)) {
                return ['ok' => true, 'message' => 'Koneksi WhatsApp berhasil.'];
            }
            return ['ok' => false, 'message' => 'API key tidak valid: ' . ($data['reason'] ?? 'Unknown error')];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Koneksi gagal: ' . $e->getMessage()];
        }
    }
}
