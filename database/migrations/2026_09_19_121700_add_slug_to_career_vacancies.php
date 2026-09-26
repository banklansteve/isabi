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
        Schema::table('career_vacancies', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('public_uid');
        });

        foreach (DB::table('career_vacancies')->orderBy('id')->get() as $row) {
            $base = Str::slug((string) $row->title) ?: 'role';
            $slug = $base;
            $n = 2;
            while (DB::table('career_vacancies')->where('slug', $slug)->where('id', '!=', $row->id)->exists()) {
                $slug = $base.'-'.$n;
                $n++;
            }
            DB::table('career_vacancies')->where('id', $row->id)->update(['slug' => $slug]);
        }

        Schema::table('career_vacancies', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('career_vacancies', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
