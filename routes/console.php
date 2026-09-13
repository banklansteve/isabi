<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('staff:sync-roles', function () {
    if (! Schema::hasTable('staff_roles')) {
        $this->error('staff_roles was not found.');

        return 1;
    }

    // The canonical duty list lives in config/admin.php and is seeded by
    // StaffRoleSeeder. It only creates missing duties, so Super Admin edits
    // to the built-in duties are preserved on every sync.
    $this->call('db:seed', ['--class' => \Database\Seeders\StaffRoleSeeder::class, '--force' => true]);

    $this->info('Operations duties are in sync with config/admin.php.');
})->purpose('Create any missing operations duties from config/admin.php');
/*
| Daily pass that finds clients who haven't reviewed after X days.
| The artisan still sends via the same WhatsApp click-to-chat sheet —
| this just keeps the "due" queue accurate for the dashboard.
*/
Schedule::command('reviews:prepare-reminders')->dailyAt('09:15');
Schedule::command('quotes:process-expiry')->dailyAt('08:30');
Schedule::command('announcements:dispatch')->everyMinute();
Schedule::command('patrol:scan')->hourly();
