<?php

namespace App\Http\Controllers;

use App\Models\WebhookEndpoint;
use App\Models\WebhookLog;
use App\Services\WebhookService;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    public function index()
    {
        $endpoints = WebhookEndpoint::withCount('logs')->orderByDesc('created_at')->get();
        return view('settings.webhooks', compact('endpoints'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'url'    => 'required|url|max:1000',
            'events' => 'required|array|min:1',
            'events.*' => 'string|in:' . implode(',', array_keys(WebhookEndpoint::EVENTS)) . ',*',
        ]);

        WebhookEndpoint::create([
            ...$data,
            'secret' => Str::random(32),
        ]);

        return back()->with('success', 'Webhook endpoint ditambahkan.');
    }

    public function update(Request $request, WebhookEndpoint $webhookEndpoint)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'url'       => 'required|url|max:1000',
            'events'    => 'required|array|min:1',
            'events.*'  => 'string',
            'is_active' => 'boolean',
        ]);
        $webhookEndpoint->update($data + ['is_active' => $request->boolean('is_active')]);
        return back()->with('success', 'Webhook diperbarui.');
    }

    public function destroy(WebhookEndpoint $webhookEndpoint)
    {
        $webhookEndpoint->delete();
        return back()->with('success', 'Webhook dihapus.');
    }

    public function regenerateSecret(WebhookEndpoint $webhookEndpoint)
    {
        $webhookEndpoint->update(['secret' => Str::random(32)]);
        return back()->with('success', 'Secret baru dibuat: ' . $webhookEndpoint->fresh()->secret);
    }

    public function logs(WebhookEndpoint $webhookEndpoint)
    {
        $logs = $webhookEndpoint->logs()->paginate(30);
        return response()->json($logs);
    }

    public function test(WebhookEndpoint $webhookEndpoint)
    {
        $ticket = Ticket::with(['user', 'assignee', 'category'])->first();
        if (!$ticket) {
            return response()->json(['ok' => false, 'message' => 'Tidak ada tiket untuk dijadikan payload test.']);
        }

        $service = app(WebhookService::class);
        $payload = $service->buildPayload('webhook.test', $ticket, ['is_test' => true]);

        // Dispatch synchronously for immediate feedback
        $endpoint = $webhookEndpoint;
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $headers = [
            'Content-Type'       => 'application/json',
            'X-Helpdesk-Event'   => 'webhook.test',
        ];
        if ($endpoint->secret) {
            $headers['X-Helpdesk-Signature'] = 'sha256=' . $endpoint->sign($body);
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)->withHeaders($headers)->withBody($body, 'application/json')->post($endpoint->url);
            $ok = $response->successful();
            $msg = "HTTP {$response->status()} — " . ($ok ? 'Berhasil' : 'Gagal');
        } catch (\Throwable $e) {
            $ok = false;
            $msg = 'Error: ' . $e->getMessage();
        }

        return response()->json(['ok' => $ok, 'message' => $msg]);
    }
}
