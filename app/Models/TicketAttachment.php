<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketAttachment extends Model
{
    use HasFactory;
    protected $fillable = ['ticket_id', 'comment_id', 'uploaded_by', 'original_name', 'stored_name', 'path', 'mime_type', 'size'];
    public function ticket() { return $this->belongsTo(Ticket::class); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
    public function getSizeFormattedAttribute(): string { return round($this->size / 1024, 1) . ' KB'; }
}
