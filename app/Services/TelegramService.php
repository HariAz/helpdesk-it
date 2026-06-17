<?php

namespace App\Services;

use App\Models\IntegrationSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private ?string $token;
    private ?string $chatId;

    public function __construct()
    {
        $settings = IntegrationSetting::getMany(['telegram_bot_token', 'telegram_chat_id']);
        $this->token  = $settings['telegram_bot_token'] ?: null;
        $this->chatId = $settings['telegram_chat_id'] ?: null;
    }

    public function isConfigured(): bool
    {
        return IntegrationSetting::getBool('telegram_enabled') && $this->token && $this->chatId;
    }

    public function send(string $text): bool
    {
        if (!$this->isConfigured()) return false;

        try {
            $response = Http::timeout(10)->post(
                "https://api.telegram.org/bot{$this->token}/sendMessage",
                ['chat_id' => $this->chatId, 'text' => $text, 'parse_mode' => 'HTML']
            );
            if (!$response->successful()) {
                Log::warning('Telegram send failed', ['status' => $response->status(), 'body' => $response->body()]);
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            Log::error('Telegram exception: ' . $e->getMessage());
            return false;
        }
    }

    public function testConnection(): array
    {
        if (!$this->token) return ['ok' => false, 'message' => 'Bot token belum dikonfigurasi.'];
        try {
            $res = Http::timeout(10)->get("https://api.telegram.org/bot{$this->token}/getMe");
            if ($res->successful()) {
                $name = $res->json('result.first_name', 'Unknown');
                return ['ok' => true, 'message' => "Bot terhubung: {$name}"];
            }
            return ['ok' => false, 'message' => 'Token tidak valid: ' . $res->json('description', 'Unknown error')];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Koneksi gagal: ' . $e->getMessage()];
        }
    }
}
