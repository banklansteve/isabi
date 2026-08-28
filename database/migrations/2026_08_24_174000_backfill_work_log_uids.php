<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('work_logs')->whereNull('uid')->orderBy('id')->get()->each(function (object $log): void {
            DB::table('work_logs')->where('id', $log->id)->update([
                'uid' => (string) Str::uuid(),
            ]);
        });
    }

    public function down(): void
    {
        //
    }
};
