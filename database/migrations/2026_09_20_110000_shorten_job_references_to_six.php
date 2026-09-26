<?php

use App\Models\JobSlugRedirect;
use App\Models\WorkLog;
use App\Support\JobReference;
use App\Support\JobSlug;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Shorten opaque job references to 6 characters (letter + digit mix).
 * Old 8-char (and other non-canonical) paths 301 forever.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('work_logs') || ! Schema::hasColumn('work_logs', 'reference')) {
            return;
        }

        WorkLog::query()
            ->select([
                'id',
                'user_id',
                'reference',
                'slug',
                'job_category',
                'job_subcategory',
            ])
            ->orderBy('id')
            ->chunkById(100, function ($logs): void {
                foreach ($logs as $log) {
                    $oldRef = filled($log->reference) ? strtolower((string) $log->reference) : null;

                    if ($oldRef && JobReference::isCanonical($oldRef)) {
                        continue;
                    }

                    $prefix = filled($log->slug)
                        ? strtolower((string) $log->slug)
                        : JobSlug::seoPrefix($log->job_category, $log->job_subcategory);

                    $oldPaths = array_values(array_unique(array_filter([
                        $oldRef,
                        ($prefix && $oldRef) ? JobSlug::composeLegacy($prefix, $oldRef) : null,
                        ($prefix && $oldRef) ? JobSlug::compose($prefix, $oldRef) : null,
                    ])));

                    $newRef = JobReference::unique($log->id);

                    $log->forceFill([
                        'slug' => $prefix,
                        'reference' => $newRef,
                    ])->saveQuietly();

                    $canonical = JobSlug::compose($prefix, $newRef);

                    foreach ($oldPaths as $old) {
                        if ($old !== $canonical) {
                            JobSlugRedirect::remember($old, $log);
                        }
                    }
                }
            });
    }

    public function down(): void
    {
        // Irreversible: shortened references are not restored.
    }
};
