<?php

namespace App\Jobs;

use App\Mail\DailySummaryMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendDailySummaryNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public array $stats) {}

    public function handle(): void
    {
        $supervisors = User::where('role', 'supervisor')->where('is_active', true)->get();
        foreach ($supervisors as $supervisor) {
            Mail::to($supervisor->email)->send(new DailySummaryMail($this->stats));
        }
    }
}
