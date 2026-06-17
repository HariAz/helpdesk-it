<?php

namespace App\Http\Controllers;

use App\Models\WorkSchedule;
use Illuminate\Http\Request;

class WorkScheduleController extends Controller
{
    public function index()
    {
        $schedules = WorkSchedule::orderBy('day_of_week')->get();
        return view('settings.work-schedule', compact('schedules'));
    }

    public function update(Request $request)
    {
        $days = $request->input('days', []);

        foreach ($days as $dayOfWeek => $data) {
            WorkSchedule::updateOrCreate(
                ['day_of_week' => $dayOfWeek],
                [
                    'start_time'     => $data['start_time'] ?? '08:00',
                    'end_time'       => $data['end_time'] ?? '17:00',
                    'is_working_day' => isset($data['is_working_day']) ? 1 : 0,
                ]
            );
        }

        return back()->with('success', 'Jadwal kerja berhasil diperbarui.');
    }
}
