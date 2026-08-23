<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_logs', function (Blueprint $table) {
            $table->string('slug', 80)->nullable()->after('uid');
        });

        $this->backfill();

        Schema::table('work_logs', function (Blueprint $table) {
            $table->unique(['user_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('work_logs', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'slug']);
            $table->dropColumn('slug');
        });
    }

    /**
     * Generated here rather than through the model so the migration stays
     * self-contained and can't be broken by later changes to the slug helper.
     */
    private function backfill(): void
    {
        $used = [];

        DB::table('work_logs')
            ->select(['id', 'user_id', 'description'])
            ->orderBy('id')
            ->chunkById(500, function ($rows) use (&$used) {
                foreach ($rows as $row) {
                    $base = Str::slug(Str::ascii((string) $row->description)) ?: 'job';

                    if (strlen($base) > 70) {
                        $trimmed = substr($base, 0, 70);
                        $lastHyphen = strrpos($trimmed, '-');
                        $base = $lastHyphen !== false && $lastHyphen > 20
                            ? substr($trimmed, 0, $lastHyphen)
                            : $trimmed;
                    }

                    $slug = $base;
                    $suffix = 2;

                    while (isset($used[$row->user_id][$slug])) {
                        $slug = $base.'-'.$suffix;
                        $suffix++;
                    }

                    $used[$row->user_id][$slug] = true;

                    DB::table('work_logs')->where('id', $row->id)->update(['slug' => $slug]);
                }
            });
    }
};
