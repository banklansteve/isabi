<?php

namespace Tests\Unit;

use App\Models\WorkLog;
use App\Support\ReviewInvite;
use Tests\TestCase;

class ReviewInviteTokenTest extends TestCase
{
    public function test_generate_token_uses_prefix_and_secure_length(): void
    {
        $token = ReviewInvite::generateToken();

        $this->assertStringStartsWith(WorkLog::REVIEW_TOKEN_PREFIX, $token);
        $body = substr($token, strlen(WorkLog::REVIEW_TOKEN_PREFIX));
        $this->assertSame(WorkLog::REVIEW_TOKEN_BODY_LENGTH, strlen($body));
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $body);

        // Total length is prefix + body (30 chars with current constants).
        $this->assertSame(
            strlen(WorkLog::REVIEW_TOKEN_PREFIX) + WorkLog::REVIEW_TOKEN_BODY_LENGTH,
            strlen($token),
        );
    }

    public function test_generated_tokens_are_unique(): void
    {
        $tokens = collect(range(1, 20))->map(fn () => ReviewInvite::generateToken());

        $this->assertSame($tokens->count(), $tokens->unique()->count());
    }
}
