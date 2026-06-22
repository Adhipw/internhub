<?php

use App\Models\Application;
use App\Models\Otp;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Clean up expired OTPs every hour
Schedule::call(function () {
    Otp::where('expires_at', '<', now())->delete();
})->hourly()->name('cleanup-expired-otps');

// Auto-withdraw pending applications older than 30 days
Schedule::call(function () {
    $staleApplications = Application::where('status', 'pending')
        ->where('created_at', '<', now()->subDays(30))
        ->get();

    foreach ($staleApplications as $app) {
        $app->update([
            'status' => 'withdrawn',
            'timeline' => array_merge($app->timeline ?? [], [[
                'status' => 'withdrawn',
                'label' => 'Dibatalkan Otomatis',
                'description' => 'Lamaran ditarik otomatis oleh sistem karena tidak ada aktivitas selama 30 hari.',
                'date' => now()->toDateTimeString(),
            ]]),
        ]);
    }
})->daily()->name('auto-withdraw-stale-applications');

// Scrape real internships every 12 hours
Schedule::command('internships:scrape')->twiceDaily(1, 13)->name('scrape-real-internships');
