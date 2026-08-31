<?php

use App\Models\WorkLog;
use App\Support\JobReference;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        WorkLog::query()
            ->orderBy('id')
            ->each(function (WorkLog $log): void {
                if (JobReference::isValid($log->reference)) {
                    return;
                }

                $log->forceFill([
                    'reference' => JobReference::unique($log->id, $log->description),
                ])->saveQuietly();
            });
    }

    public function down(): void
    {
        // References are not reverted — old ISB-prefixed codes are intentionally retired.
    }
};
