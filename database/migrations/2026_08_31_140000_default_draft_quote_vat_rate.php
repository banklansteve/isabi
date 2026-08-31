<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('artisan_quotes')
            ->where('status', 'draft')
            ->whereNull('sent_at')
            ->where('vat_rate', 0)
            ->update(['vat_rate' => 7.5]);
    }

    public function down(): void
    {
        // No-op — cannot reliably restore prior zero rates.
    }
};
