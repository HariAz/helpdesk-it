<?php

namespace App\Models;

use App\Services\BusinessHoursCalculator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlaConfig extends Model
{
    use HasFactory;

    protected $fillable = ['priority', 'category_id', 'response_time_hours', 'resolution_time_hours'];

    public function category() { return $this->belongsTo(Category::class); }

    public static function deadlineFor(string $priority, Carbon $from = null, ?int $categoryId = null): Carbon
    {
        $from ??= now();

        // Try category-specific config first, then fall back to global
        $config = null;
        if ($categoryId) {
            $config = static::where('priority', $priority)
                ->where('category_id', $categoryId)
                ->first();
        }
        $config ??= static::where('priority', $priority)->whereNull('category_id')->first();

        $hours = $config?->resolution_time_hours ?? 24;

        // Use business hours if work_schedules are configured
        if (WorkSchedule::where('is_working_day', true)->exists()) {
            return (new BusinessHoursCalculator())->addBusinessHours($from, $hours);
        }

        return $from->copy()->addHours($hours);
    }

    public static function responseDeadlineFor(string $priority, Carbon $from = null, ?int $categoryId = null): Carbon
    {
        $from ??= now();

        $config = null;
        if ($categoryId) {
            $config = static::where('priority', $priority)->where('category_id', $categoryId)->first();
        }
        $config ??= static::where('priority', $priority)->whereNull('category_id')->first();

        $hours = $config?->response_time_hours ?? 4;

        if (WorkSchedule::where('is_working_day', true)->exists()) {
            return (new BusinessHoursCalculator())->addBusinessHours($from, $hours);
        }

        return $from->copy()->addHours($hours);
    }
}
