<?php

use App\Models\JobSlugRedirect;
use App\Models\WorkLog;
use App\Support\JobSlug;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SEO prefix is shared across jobs; the opaque reference is the unique key.
        try {
            DB::statement('ALTER TABLE work_logs DROP INDEX work_logs_user_id_slug_unique');
        } catch (\Throwable) {
            // Already dropped from a partial run.
        }

        if (! Schema::hasTable('job_slug_redirects')) {
            Schema::create('job_slug_redirects', function (Blueprint $table) {
                $table->id();
                $table->string('from_path', 160)->unique();
                $table->foreignId('work_log_id')->constrained('work_logs')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->index(['user_id', 'from_path']);
            });
        }

        WorkLog::query()
            ->select([
                'id',
                'user_id',
                'reference',
                'slug',
                'subject',
                'description',
                'job_category',
                'job_subcategory',
            ])
            ->orderBy('id')
            ->chunkById(100, function ($logs): void {
                foreach ($logs as $log) {
                    if (blank($log->reference)) {
                        continue;
                    }

                    $ref = strtolower((string) $log->reference);
                    $oldSlug = filled($log->slug) ? strtolower((string) $log->slug) : null;

                    $oldPaths = array_values(array_unique(array_filter([
                        $oldSlug,
                        $oldSlug && ! str_ends_with($oldSlug, '-'.$ref)
                            ? JobSlug::compose($oldSlug, $ref)
                            : null,
                        $ref,
                    ])));

                    $prefix = JobSlug::seoPrefix($log->job_category, $log->job_subcategory);
                    $log->forceFill(['slug' => $prefix])->saveQuietly();

                    $canonical = JobSlug::compose($prefix, $ref);

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
        Schema::dropIfExists('job_slug_redirects');

        Schema::table('work_logs', function (Blueprint $table) {
            $table->unique(['user_id', 'slug'], 'work_logs_user_id_slug_unique');
        });
    }
};
