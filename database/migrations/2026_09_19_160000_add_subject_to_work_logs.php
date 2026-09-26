<?php

use App\Models\WorkLog;
use App\Support\JobSlug;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public const SUBJECT_MAX = 80;

    public function up(): void
    {
        Schema::table('work_logs', function (Blueprint $table) {
            $table->string('subject', self::SUBJECT_MAX)->nullable()->after('slug');
        });

        WorkLog::query()
            ->select(['id', 'user_id', 'description', 'slug', 'subject'])
            ->orderBy('id')
            ->chunkById(200, function ($logs): void {
                foreach ($logs as $log) {
                    $subject = Str::limit(trim((string) $log->description), self::SUBJECT_MAX, '');
                    if ($subject === '') {
                        $subject = 'Completed job';
                    }

                    $slug = filled($log->slug)
                        ? $log->slug
                        : JobSlug::uniqueFor((int) $log->user_id, $subject, (int) $log->id);

                    DB::table('work_logs')->where('id', $log->id)->update([
                        'subject' => $subject,
                        'slug' => $slug,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('work_logs', function (Blueprint $table) {
            $table->dropColumn('subject');
        });
    }
};
