<?php

namespace App\Rules;

use App\Support\ProfileSlug;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AvailableProfileSlug implements ValidationRule
{
    public function __construct(
        private readonly ?int $ignoreUserId = null,
        private readonly bool $allowEmpty = false,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $raw = is_string($value) ? trim($value) : '';

        if ($raw === '') {
            if (! $this->allowEmpty) {
                $fail('Enter a business name for your public page URL.');
            }

            return;
        }

        $slug = ProfileSlug::normalize($raw);

        if ($slug === '') {
            $fail('Use letters or numbers in your business name so we can build a URL.');

            return;
        }

        if (ProfileSlug::isReserved($slug)) {
            $fail('That name is reserved. Try a different business name.');

            return;
        }

        if (ProfileSlug::isTaken($slug, $this->ignoreUserId)) {
            $fail('That public URL is already taken. Try another name.');
        }
    }
}
