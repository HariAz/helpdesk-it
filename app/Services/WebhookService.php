<?php

namespace App\Services;

use App\Jobs\DispatchWebhook;
use App\Models\Ticket;
use App\Models\WebhookEndpoint;

class WebhookService
{
    public function fire(string $event, Ticket $ticket, array $extra = []): void
    {
        $endpoints = WebhookEndpoint::where('is_active', true)->get()->filter(
            fn($ep) => $ep->listensTo($event)
        );

        if ($endpoints->isEmpty()) return;

        $payload = $this->buildPayload($event, $ticket, $extra);

        foreach ($endpoints as $endpoint) {
            DispatchWebhook::dispatch($endpoint, $event, $payload);
        }
    }

    public function buildPayload(string $event, Ticket $ticket, array $extra = []): array
    {
        $ticket->loadMissing(['user', 'assignee', 'category', 'subcategory']);

        return [
            'event'     => $event,
            'timestamp' => now()->toIso8601String(),
            'data'      => array_merge([
                'id'            => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'title'         => $ticket->title,
                'status'        => $ticket->status,
                'priority'      => $ticket->priority,
                'is_escalated'  => $ticket->is_escalated,
                'sla_deadline'  => $ticket->sla_deadline?->toIso8601String(),
                'resolved_at'   => $ticket->resolved_at?->toIso8601String(),
                'created_at'    => $ticket->created_at->toIso8601String(),
                'user'      => $ticket->user ? ['id' => $ticket->user->id, 'name' => $ticket->user->name, 'email' => $ticket->user->email] : null,
                'assignee'  => $ticket->assignee ? ['id' => $ticket->assignee->id, 'name' => $ticket->assignee->name] : null,
                'category'  => $ticket->category ? ['id' => $ticket->category->id, 'name' => $ticket->category->name] : null,
                'url'       => url('/tickets/' . $ticket->id),
            ], $extra),
        ];
    }
}
