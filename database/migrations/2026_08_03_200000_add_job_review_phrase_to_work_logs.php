<?php

use App\Models\WorkLog;
use App\Support\JobCategories;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_logs', function (Blueprint $table) {
            $table->string('job_review_phrase', 160)->nullable()->after('job_subcategory');
        });

        WorkLog::query()
            ->whereNotNull('job_subcategory')
            ->orderBy('id')
            ->each(function (WorkLog $log): void {
                $phrase = JobCategories::reviewPhrase($log->job_category, $log->job_subcategory);
                if ($phrase) {
                    $log->forceFill(['job_review_phrase' => $phrase])->saveQuietly();
                }
            });
    }

    public function down(): void
    {
        Schema::table('work_logs', function (Blueprint $table) {
            $table->dropColumn('job_review_phrase');
        });
    }
};