<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('staff:sync-roles', function () {
    $db = DB::connection();
    $schema = Schema::connection($db->getName());

    if (! $schema->hasTable('staff_roles')) {
        $this->error('staff_roles was not found.');

        return 1;
    }

    if (! $schema->hasColumn('staff_roles', 'key')) {
        $schema->table('staff_roles', function ($table) {
            $table->string('key', 64)->nullable();
        });
    }

    $now = now();
    $roles = [
        ['key' => 'support_agent', 'name' => 'Customer Support Agent', 'old' => ['Customer support', 'Support'], 'icon' => 'ti ti-headset'],
        ['key' => 'trust_safety', 'name' => 'Trust & Safety / Moderator', 'old' => ['Moderation', 'Moderator'], 'icon' => 'ti ti-shield-exclamation'],
        ['key' => 'verification_officer', 'name' => 'Verification Officer', 'old' => ['Patrol', 'Patrolling'], 'icon' => 'ti ti-user-check'],
        ['key' => 'finance_officer', 'name' => 'Finance & Billing Officer', 'old' => ['Finance'], 'icon' => 'ti ti-report-money'],
        ['key' => 'growth_ops', 'name' => 'Growth & Referral Ops', 'old' => ['Growth'], 'icon' => 'ti ti-chart-arrows'],
        ['key' => 'content_comms', 'name' => 'Content & Communications Manager', 'old' => ['Content'], 'icon' => 'ti ti-speakerphone'],
    ];

    $columns = collect($db->select('SHOW COLUMNS FROM staff_roles'))->keyBy('Field');

    foreach ($roles as $index => $role) {
        $row = $db->table('staff_roles')
            ->where('key', $role['key'])
            ->orWhere('slug', $role['key'])
            ->orWhereIn('name', array_merge([$role['name']], $role['old']))
            ->orWhereIn('slug', ['customer-support', 'moderation', 'patrol', 'customer_support'])
            ->first();

        $payload = [];
        foreach ($columns as $field => $meta) {
            if ($field === 'id' || str_contains((string) $meta->Extra, 'auto_increment')) {
                continue;
            }

            $payload[$field] = match ($field) {
                'key', 'slug', 'code' => $role['key'],
                'name', 'title', 'label' => $role['name'],
                'icon' => $role['icon'],
                'sort_order', 'position', 'order' => ($index + 1) * 10,
                'locked', 'is_locked' => 0,
                'created_at' => $row->created_at ?? $now,
                'updated_at' => $now,
                'guard_name', 'guard' => 'web',
                default => $row->{$field} ?? ($meta->Default !== null || strtoupper((string) $meta->Null) === 'YES' ? null : ''),
            };

            if ($payload[$field] === null && strtoupper((string) $meta->Null) !== 'YES' && $meta->Default === null) {
                $payload[$field] = in_array($field, ['key', 'slug', 'code'], true) ? $role['key'] : '';
            }
        }

        $payload = array_filter($payload, fn ($value) => $value !== null);

        if ($row) {
            unset($payload['created_at']);
            $db->table('staff_roles')->where('id', $row->id)->update($payload);
        } else {
            $db->table('staff_roles')->insert($payload);
        }
    }

    $this->info('The six operations roles are ready.');
})->purpose('Insert the six operations staff roles');
/*
| Daily pass that finds clients who haven't reviewed after X days.
| The artisan still sends via the same WhatsApp click-to-chat sheet —
| this just keeps the "due" queue accurate for the dashboard.
*/
Schedule::command('reviews:prepare-reminders')->dailyAt('09:15');
Schedule::command('announcements:dispatch')->everyMinute();
