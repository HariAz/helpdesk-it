<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketStatusLog extends Model
{
    use HasFactory;
    protected $fillable = ['ticket_id', 'user_id', 'from_status', 'to_status', 'note'];
    public function ticket() { return $this->belongsTo(Ticket::class); }
    public function user() { return $this->belongsTo(User::class); }
}
