<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'department', 'phone',
        'is_active', 'last_login_at', 'last_login_ip',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function isSupervisor(): bool { return $this->role === 'supervisor'; }
    public function isTeknisi(): bool { return $this->role === 'teknisi'; }
    public function isUser(): bool { return $this->role === 'user'; }

    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->name);
        return strtoupper(implode('', array_map(fn($w) => $w[0], array_slice($words, 0, 2))));
    }

    public function tickets() { return $this->hasMany(Ticket::class); }
    public function assignedTickets() { return $this->hasMany(Ticket::class, 'assigned_to'); }
    public function comments() { return $this->hasMany(TicketComment::class); }
    public function activityLogs() { return $this->hasMany(ActivityLog::class); }
}
