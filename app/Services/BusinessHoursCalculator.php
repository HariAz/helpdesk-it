<?php

namespace App\Services;

use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BusinessHoursCalculator
{
    private Collection $schedules;

    public function __construct()
    {
        $this->schedules = WorkSchedule::all()->keyBy('day_of_week');
    }

    public function addBusinessHours(Carbon $from, float $hours): Carbon
    {
        $current = $from->copy();
        $minutesLeft = (int) round($hours * 60);

        // Advance to the next working minute if currently outside hours
        $current = $this->nextWorkingMinute($current);

        while ($minutesLeft > 0) {
            $schedule = $this->schedules->get($current->dayOfWeek);

            if (!$schedule || !$schedule->is_working_day) {
                $current = $this->nextWorkingDayStart($current->addDay());
                continue;
            }

            [$endH, $endM] = explode(':', $schedule->end_time);
            $endOfDay = $current->copy()->setTime((int) $endH, (int) $endM, 0);

            if ($current->gte($endOfDay)) {
                $current = $this->nextWorkingDayStart($current->addDay());
                continue;
            }

            $minutesUntilEnd = (int) $current->diffInMinutes($endOfDay);

            if ($minutesLeft <= $minutesUntilEnd) {
                $current->addMinutes($minutesLeft);
                $minutesLeft = 0;
            } else {
                $minutesLeft -= $minutesUntilEnd;
                $current = $this->nextWorkingDayStart($current->addDay());
            }
        }

        return $current;
    }

    public function businessMinutesBetween(Carbon $start, Carbon $end): int
    {
        if ($end->lte($start)) return 0;

        $current = $start->copy();
        $minutes = 0;

        while ($current->lt($end)) {
            $schedule = $this->schedules->get($current->dayOfWeek);

            if (!$schedule || !$schedule->is_working_day) {
                $current->addDay()->startOfDay();
                continue;
            }

            [$startH, $startM] = explode(':', $schedule->start_time);
            [$endH, $endM]     = explode(':', $schedule->end_time);

            $dayStart = $current->copy()->setTime((int) $startH, (int) $startM, 0);
            $dayEnd   = $current->copy()->setTime((int) $endH, (int) $endM, 0);

            $from = $current->lt($dayStart) ? $dayStart : $current;
            $to   = $end->lt($dayEnd) ? $end : $dayEnd;

            if ($to->gt($from)) {
                $minutes += (int) $from->diffInMinutes($to);
            }

            $current = $dayEnd->addSecond();
        }

        return $minutes;
    }

    private function nextWorkingMinute(Carbon $dt): Carbon
    {
        $limit = 0;
        while ($limit++ < 14) { // max 2 weeks to find a working day
            $schedule = $this->schedules->get($dt->dayOfWeek);

            if (!$schedule || !$schedule->is_working_day) {
                $dt = $this->nextWorkingDayStart($dt->addDay());
                continue;
            }

            [$startH, $startM] = explode(':', $schedule->start_time);
            [$endH, $endM]     = explode(':', $schedule->end_time);

            $dayStart = $dt->copy()->setTime((int) $startH, (int) $startM, 0);
            $dayEnd   = $dt->copy()->setTime((int) $endH, (int) $endM, 0);

            if ($dt->lt($dayStart)) return $dayStart;
            if ($dt->lt($dayEnd))   return $dt;

            // past end of today — go to next working day
            $dt = $this->nextWorkingDayStart($dt->addDay());
        }

        return $dt;
    }

    private function nextWorkingDayStart(Carbon $dt): Carbon
    {
        $dt = $dt->copy()->startOfDay();
        $limit = 0;
        while ($limit++ < 14) {
            $schedule = $this->schedules->get($dt->dayOfWeek);
            if ($schedule && $schedule->is_working_day) {
                [$h, $m] = explode(':', $schedule->start_time);
                return $dt->setTime((int) $h, (int) $m, 0);
            }
            $dt->addDay();
        }
        return $dt;
    }
}
