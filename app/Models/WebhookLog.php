<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $fillable = ['webhook_endpoint_id', 'event', 'payload', 'response_status', 'response_body', 'error', 'success', 'sent_at'];

    protected $casts = [
        'payload'   => 'array',
        'success'   => 'boolean',
        'sent_at'   => 'datetime',
    ];

    public function endpoint() { return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id'); }
}
