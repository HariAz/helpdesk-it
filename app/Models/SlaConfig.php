<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlaConfig extends Model
{
    use HasFactory;
    protected $fillable = ['priority', 'response_time_hours', 'resolution_time_hours'];

    public static function deadlineFor(string $priority, \Carbon\Carbon $from = null): \Carbon\Carbon
    {
        $config = static::where('priority', $priority)->first();
        $hours = $config ? $config->resolution_time_hours : 24;
        return ($from ?? now())->addHours($hours);
    }
}
