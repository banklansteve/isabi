<?php

use App\Models\JobSlugRedirect;
use App\Models\WorkLog;
use App\Support\JobReference;
use App\Support\JobSlug;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Move job public URLs to /p/{artisan}/{seo-context}/{opaque-ref}
 * and regenerate short fixed-length references. Old paths 301 forever.
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
                    $oldSlug = filled($log->slug) ? strtolower((string) $log->slug) : null;

                    $oldPaths = array_values(array_unique(array_filter([
                        $oldRef,
                        $oldSlug,
                        ($oldSlug && $oldRef) ? JobSlug::composeLegacy($oldSlug, $oldRef) : null,
                        ($oldSlug && $oldRef) ? JobSlug::compose($oldSlug, $oldRef) : null,
                    ])));

                    $prefix = JobSlug::seoPrefix($log->job_category, $log->job_subcategory);
                    $needsNewRef = ! $oldRef || ! JobReference::isCanonical($oldRef);
                    $newRef = $needsNewRef
                        ? JobReference::unique($log->id)
                        : $oldRef;

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

                    // Remember hyphenated form of the new prefix + old ref when the ID changed.
                    if ($needsNewRef && $oldRef) {
                        JobSlugRedirect::remember(JobSlug::composeLegacy($prefix, $oldRef), $log);
                        JobSlugRedirect::remember(JobSlug::compose($prefix, $oldRef), $log);
                    }
                }
            });
    }

    public function down(): void
    {
        // Irreversible: references and redirect map are not restored.
    }
};
