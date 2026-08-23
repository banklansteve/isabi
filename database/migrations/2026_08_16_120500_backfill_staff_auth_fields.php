<?php

use App\Enums\StaffStatus;
use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereIn('role', [UserRole::SuperAdmin->value, UserRole::OperationsAdmin->value])
            ->whereNotNull('password')
            ->whereNull('password_set_at')
            ->update([
                'password_set_at' => now(),
                'staff_status' => StaffStatus::Active->value,
            ]);
    }

    public function down(): void
    {
        //
    }
};
