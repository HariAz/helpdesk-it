<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RatingToken extends Model
{
    use HasFactory;
    protected $fillable = ['ticket_id', 'token', 'is_used', 'expires_at'];
    protected $casts = ['is_used' => 'boolean', 'expires_at' => 'datetime'];
    public function ticket() { return $this->belongsTo(Ticket::class); }
    public function isValid(): bool { return !$this->is_used && $this->expires_at->isFuture(); }
}
