<?php

use App\Enums\UserRole;
use App\Support\Identity\UserUid;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'uid')) {
            return;
        }

        DB::table('users')->orderBy('id')->get(['id', 'uid', 'role'])->each(function (object $user): void {
            if (UserUid::matches($user->uid, $user->role)) {
                return;
            }

            DB::table('users')->where('id', $user->id)->update([
                'uid' => UserUid::unique((int) $user->id, UserUid::prefixFor($user->role)),
            ]);
        });
    }

    public function down(): void
    {
        // Prefixes are a one-way identity change; leaving issued uids in place.
    }
};
