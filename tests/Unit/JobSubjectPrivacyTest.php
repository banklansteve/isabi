<?php

namespace Tests\Unit;

use App\Support\WorkLog\JobSubjectPrivacy;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class JobSubjectPrivacyTest extends TestCase
{
    public function test_safe_trade_titles_are_allowed(): void
    {
        $this->assertNull(JobSubjectPrivacy::violation('Kitchen sink leak repair'));
        $this->assertNull(JobSubjectPrivacy::violation('Full house rewiring — 3-bed bungalow'));
        $this->assertNull(JobSubjectPrivacy::violation('AC gas refill and filter clean'));
    }

    #[DataProvider('unsafeSubjects')]
    public function test_identifying_details_are_blocked(string $subject, ?string $clientName = null, ?string $whatsapp = null): void
    {
        $this->assertNotNull(JobSubjectPrivacy::violation($subject, $clientName, $whatsapp));
    }

    /**
     * @return array<string, array{0: string, 1?: string|null, 2?: string|null}>
     */
    public static function unsafeSubjects(): array
    {
        return [
            'honorific name' => ['Mrs Adeyemi kitchen plumbing'],
            'phone number' => ['Sink repair — call 08031234567'],
            'email' => ['Wiring job for client@example.com'],
            'house number' => ['No. 12 Admiralty Way plumbing'],
            'street address' => ['Fixed leak at 14 Bode Thomas Street'],
            'client name overlap' => ['Adeyemi kitchen remodel', 'Mrs Adeyemi', null],
            'whatsapp digits' => ['Job for 08031234567', null, '08031234567'],
        ];
    }
}
