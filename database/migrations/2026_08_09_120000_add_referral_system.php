<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 16)->nullable()->unique()->after('slug');
            $table->foreignId('referred_by_user_id')
                ->nullable()
                ->after('referral_code')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('referred_at')->nullable()->after('referred_by_user_id');
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_user_id')->constrained('users')->cascadeOnDelete()->unique();
            $table->string('code_used', 32)->nullable();
            $table->string('status', 32)->default('signed_up');
            $table->unsignedInteger('reward_tokens')->default(0);
            $table->foreignId('token_transaction_id')
                ->nullable()
                ->constrained('token_transactions')
                ->nullOnDelete();
            $table->timestamp('qualified_at')->nullable();
            $table->timestamp('rewarded_at')->nullable();
            $table->timestamps();

            $table->index(['referrer_user_id', 'status']);
        });

        User::query()->whereNull('referral_code')->orderBy('id')->each(function (User $user): void {
            $code = null;
            for ($i = 0; $i < 12; $i++) {
                $candidate = strtoupper(Str::random(8));
                if (! User::query()->where('referral_code', $candidate)->exists()) {
                    $code = $candidate;
                    break;
                }
            }

            if ($code) {
                $user->forceFill(['referral_code' => $code])->saveQuietly();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by_user_id');
            $table->dropColumn(['referral_code', 'referred_at']);
        });
    }
};
