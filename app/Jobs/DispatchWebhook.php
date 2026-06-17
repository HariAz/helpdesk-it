<?php

namespace App\Jobs;

use App\Models\WebhookEndpoint;
use App\Models\WebhookLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DispatchWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 30;

    public function __construct(
        public WebhookEndpoint $endpoint,
        public string $event,
        public array $payload
    ) {}

    public function handle(): void
    {
        $body    = json_encode($this->payload, JSON_UNESCAPED_UNICODE);
        $headers = ['Content-Type' => 'application/json', 'X-Helpdesk-Event' => $this->event];

        if ($this->endpoint->secret) {
            $headers['X-Helpdesk-Signature'] = 'sha256=' . $this->endpoint->sign($body);
        }

        $responseStatus = null;
        $responseBody   = null;
        $error          = null;
        $success        = false;

        try {
            $response = Http::timeout(15)->withHeaders($headers)->withBody($body, 'application/json')
                ->post($this->endpoint->url);
            $responseStatus = $response->status();
            $responseBody   = substr($response->body(), 0, 2000);
            $success        = $response->successful();

            if (!$success) {
                Log::warning("Webhook to {$this->endpoint->url} returned {$responseStatus}");
            }
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            Log::error("Webhook dispatch failed: {$error}");
            $this->fail($e);
        }

        WebhookLog::create([
            'webhook_endpoint_id' => $this->endpoint->id,
            'event'               => $this->event,
            'payload'             => $this->payload,
            'response_status'     => $responseStatus,
            'response_body'       => $responseBody,
            'error'               => $error,
            'success'             => $success,
            'sent_at'             => now(),
        ]);

        $this->endpoint->update(['last_triggered_at' => now()]);
    }
}
