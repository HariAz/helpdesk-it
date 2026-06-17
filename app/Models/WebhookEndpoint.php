<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookEndpoint extends Model
{
    protected $fillable = ['name', 'url', 'events', 'secret', 'is_active', 'last_triggered_at'];

    protected $casts = [
        'events'            => 'array',
        'is_active'         => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    const EVENTS = [
        'ticket.created'        => 'Tiket Dibuat',
        'ticket.assigned'       => 'Tiket Ditugaskan',
        'ticket.status_changed' => 'Status Berubah',
        'ticket.resolved'       => 'Tiket Diselesaikan',
        'ticket.escalated'      => 'SLA Terlampaui',
        'ticket.commented'      => 'Komentar Baru',
    ];

    public function logs() { return $this->hasMany(WebhookLog::class)->orderByDesc('sent_at'); }

    public function listensTo(string $event): bool
    {
        return in_array('*', $this->events ?? []) || in_array($event, $this->events ?? []);
    }

    public function sign(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->secret ?? '');
    }
}
