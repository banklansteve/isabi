<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WorkLog;
use App\Support\Patrol\PatrolRunner;
use Illuminate\Database\Seeder;

class PatrolDemoSeeder extends Seeder
{
    public function run(): void
    {
        $artisan = User::query()->where('email', 'user@kraftrack.test')->first()
            ?? User::query()->where('email', 'many-jobs@kraftrack.test')->first();

        if (! $artisan) {
            return;
        }

        $base = now()->subMinutes(12);

        for ($i = 0; $i < 5; $i++) {
            WorkLog::withoutEvents(fn () => WorkLog::query()->create([
                'user_id' => $artisan->id,
                'description' => $i === 4 ? 'done' : 'Completed a quick wiring check on site '.$i,
                'worked_on' => $i === 4 ? now()->subDays(11)->toDateString() : now()->toDateString(),
                'client_name' => 'Demo client',
                'client_whatsapp' => '0803555000'.$i,
                'created_at' => $base->copy()->addMinutes($i),
                'updated_at' => $base->copy()->addMinutes($i),
            ]));
        }

        app(PatrolRunner::class)->scan(null, 50);
    }
}
