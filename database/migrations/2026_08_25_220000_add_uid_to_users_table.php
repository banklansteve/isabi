<?php

use App\Support\Identity\UserUid;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'uid')) {
                $table->char('uid', UserUid::LENGTH)->nullable()->unique()->after('id');
            }
        });

        DB::table('users')->whereNull('uid')->orWhere('uid', '')->orderBy('id')->get()->each(function (object $user): void {
            DB::table('users')->where('id', $user->id)->update([
                'uid' => UserUid::unique((int) $user->id),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'uid')) {
                $table->dropUnique(['uid']);
                $table->dropColumn('uid');
            }
        });
    }
};
