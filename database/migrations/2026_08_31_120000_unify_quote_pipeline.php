<?php

use App\Models\ArtisanQuote;
use App\Models\QuoteRequest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->string('subject', 180)->nullable()->after('email');
            $table->string('client_token', 64)->nullable()->unique()->after('status');
            $table->timestamp('client_token_expires_at')->nullable()->after('client_token');
            $table->text('client_response')->nullable()->after('client_token_expires_at');
            $table->timestamp('client_responded_at')->nullable()->after('client_response');
            $table->timestamp('accepted_at')->nullable()->after('client_responded_at');
            $table->foreignId('logged_work_log_id')->nullable()->after('accepted_at')
                ->constrained('work_logs')->nullOnDelete();
        });

        QuoteRequest::query()->with('artisanQuote')->each(function (QuoteRequest $request): void {
            $quote = $request->artisanQuote;
            $next = match ($request->status) {
                'contacted' => $quote?->status === ArtisanQuote::STATUS_SENT
                    ? QuoteRequest::STATUS_AWAITING_CLIENT
                    : QuoteRequest::STATUS_DRAFT,
                'closed' => QuoteRequest::STATUS_DECLINED,
                default => $request->status,
            };

            if ($next !== $request->status) {
                $request->forceFill(['status' => $next])->saveQuietly();
            }
        });
    }

    public function down(): void
    {
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('logged_work_log_id');
            $table->dropColumn([
                'subject',
                'client_token',
                'client_token_expires_at',
                'client_response',
                'client_responded_at',
                'accepted_at',
            ]);
        });
    }
};
