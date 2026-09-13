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
            $table->string('public_uid', 32)->nullable()->after('id');
            $table->string('work_mode')->nullable()->after('employment_type');
            $table->string('status', 32)->default('draft')->after('work_mode');
            $table->unsignedSmallInteger('openings')->default(1)->after('status');
            $table->date('closes_at')->nullable()->after('published_at');
            $table->foreignId('hiring_manager_id')->nullable()->after('closes_at')->constrained('users')->nullOnDelete();
            $table->foreignId('staff_role_id')->nullable()->after('hiring_manager_id')->constrained('staff_roles')->nullOnDelete();
            $table->text('requirements')->nullable()->after('description');
            $table->unsignedInteger('salary_min')->nullable()->after('requirements');
            $table->unsignedInteger('salary_max')->nullable()->after('salary_min');
            $table->string('salary_currency', 8)->default('NGN')->after('salary_max');
            $table->boolean('salary_is_public')->default(false)->after('salary_currency');
            $table->boolean('is_public')->default(false)->after('salary_is_public');
            $table->text('internal_notes')->nullable()->after('is_public');

            $table->index(['status', 'is_public']);
            $table->index('closes_at');
        });

        if (Schema::hasColumn('career_vacancies', 'is_published')) {
            foreach (DB::table('career_vacancies')->orderBy('id')->get() as $row) {
                DB::table('career_vacancies')->where('id', $row->id)->update([
                    'status' => $row->is_published ? 'open' : 'draft',
                    'is_public' => (bool) $row->is_published,
                    'public_uid' => $row->public_uid ?: Str::lower(Str::random(12)),
                ]);
            }
        }

        Schema::table('career_vacancies', function (Blueprint $table) {
            $table->unique('public_uid');
        });

        Schema::create('career_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_vacancy_id')->constrained('career_vacancies')->cascadeOnDelete();
            $table->string('public_uid', 32)->unique();
            $table->string('status', 32)->default('received');

            $table->string('full_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('city')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('portfolio_url')->nullable();

            $table->json('education')->nullable();
            $table->text('certifications')->nullable();
            $table->text('secondary_education')->nullable();
            $table->text('primary_education')->nullable();
            $table->boolean('no_work_experience')->default(false);
            $table->json('work_experience')->nullable();
            $table->json('skills')->nullable();
            $table->text('skills_other')->nullable();
            $table->text('achievements')->nullable();

            $table->string('nysc_status')->nullable();
            $table->boolean('willing_to_relocate')->nullable();
            $table->string('preferred_work_mode')->nullable();
            $table->string('earliest_availability')->nullable();

            $table->string('expected_salary')->nullable();
            $table->string('notice_period')->nullable();
            $table->text('why_this_role')->nullable();

            $table->string('cv_disk')->nullable();
            $table->string('cv_path')->nullable();
            $table->string('cv_url')->nullable();
            $table->string('cv_name')->nullable();
            $table->string('work_sample_url')->nullable();
            $table->json('references')->nullable();

            $table->boolean('ndpr_consent')->default(false);
            $table->timestamp('ndpr_consented_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->index(['career_vacancy_id', 'status']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_applications');

        Schema::table('career_vacancies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hiring_manager_id');
            $table->dropConstrainedForeignId('staff_role_id');
            $table->dropUnique(['public_uid']);
            $table->dropColumn([
                'public_uid',
                'work_mode',
                'status',
                'openings',
                'closes_at',
                'requirements',
                'salary_min',
                'salary_max',
                'salary_currency',
                'salary_is_public',
                'is_public',
                'internal_notes',
            ]);
        });
    }
};
