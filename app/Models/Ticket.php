<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ticket_number', 'title', 'description', 'user_id', 'assigned_to',
        'category_id', 'subcategory_id', 'priority', 'status',
        'sla_deadline', 'is_escalated', 'resolved_at', 'closed_at',
    ];

    protected $casts = [
        'sla_deadline' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'is_escalated' => 'boolean',
    ];

    const STATUS_LABELS = [
        'open' => 'Open', 'assigned' => 'Assigned', 'in_progress' => 'In Progress',
        'pending_user' => 'Pending User', 'resolved' => 'Resolved',
        'closed' => 'Closed', 'cancelled' => 'Cancelled', 'reopened' => 'Reopened',
    ];

    const STATUS_COLORS = [
        'open' => 'secondary', 'assigned' => 'info', 'in_progress' => 'primary',
        'pending_user' => 'warning', 'resolved' => 'success',
        'closed' => 'dark', 'cancelled' => 'danger', 'reopened' => 'warning',
    ];

    const PRIORITY_COLORS = [
        'kritis' => 'danger', 'tinggi' => 'warning', 'sedang' => 'info', 'rendah' => 'secondary',
    ];

    public function getSlaStatusAttribute(): string
    {
        if (!$this->sla_deadline) return 'none';
        $percent = now()->diffInMinutes($this->created_at) / $this->sla_deadline->diffInMinutes($this->created_at) * 100;
        if ($percent >= 100) return 'overdue';
        if ($percent >= 75) return 'warning';
        return 'ok';
    }

    public function user() { return $this->belongsTo(User::class); }
    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function category() { return $this->belongsTo(Category::class); }
    public function subcategory() { return $this->belongsTo(Category::class, 'subcategory_id'); }
    public function comments() { return $this->hasMany(TicketComment::class); }
    public function publicComments() { return $this->hasMany(TicketComment::class)->where('is_internal', false); }
    public function internalNotes() { return $this->hasMany(TicketComment::class)->where('is_internal', true); }
    public function attachments() { return $this->hasMany(TicketAttachment::class); }
    public function statusLogs() { return $this->hasMany(TicketStatusLog::class)->orderBy('created_at'); }
    public function assignments() { return $this->hasMany(TicketAssignment::class); }
    public function rating() { return $this->hasOne(TicketRating::class); }
    public function ratingToken() { return $this->hasOne(RatingToken::class); }
}
