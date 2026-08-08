<?php

use App\Models\User;
use App\Models\WorkLog;
use App\Support\ProfileSlug;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('business_name', 120)->nullable()->after('last_name');
            $table->string('slug', 140)->nullable()->unique()->after('business_name');
            $table->unsignedTinyInteger('slug_change_count')->default(0)->after('slug');
            $table->timestamp('slug_changed_at')->nullable()->after('slug_change_count');
            $table->string('bio', 500)->nullable()->after('whatsapp');
            $table->string('avatar_path')->nullable()->after('bio');
            $table->string('avatar_url', 2048)->nullable()->after('avatar_path');
        });

        Schema::create('profile_slug_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_slug', 140)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::table('work_logs', function (Blueprint $table) {
            $table->uuid('uid')->nullable()->unique()->after('id');
            $table->string('review_token', 64)->nullable()->unique()->after('review_requested_at');
            $table->timestamp('review_token_expires_at')->nullable()->after('review_token');
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('work_log_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->string('client_display_name', 120)->nullable();
            $table->string('referred_by', 120)->nullable();
            $table->string('photo_disk', 32)->nullable();
            $table->string('photo_path')->nullable();
            $table->string('photo_url', 2048)->nullable();
            $table->string('submitter_ip_hash', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->unique('work_log_id');
            $table->index(['user_id', 'submitted_at']);
        });

        WorkLog::query()->whereNull('uid')->orderBy('id')->each(function (WorkLog $log): void {
            $log->forceFill(['uid' => (string) Str::uuid()])->saveQuietly();
        });

        User::query()->whereNull('slug')->orderBy('id')->each(function (User $user): void {
            $preferred = $user->business_name
                ?: trim(($user->first_name ?? '').' '.($user->last_name ?? ''))
                ?: $user->name
                ?: 'artisan';

            $slug = ProfileSlug::uniqueFrom($preferred, $user->id);

            $user->forceFill([
                'business_name' => $user->business_name ?: $preferred,
                'slug' => $slug,
            ])->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');

        Schema::table('work_logs', function (Blueprint $table) {
            $table->dropColumn(['uid', 'review_token', 'review_token_expires_at']);
        });

        Schema::dropIfExists('profile_slug_redirects');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'business_name',
                'slug',
                'slug_change_count',
                'slug_changed_at',
                'bio',
                'avatar_path',
                'avatar_url',
            ]);
        });
    }
};
