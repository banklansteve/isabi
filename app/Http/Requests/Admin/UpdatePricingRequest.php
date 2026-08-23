<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'free.monthly_review_links' => ['required', 'integer', 'min:0', 'max:1000'],
            'annual.price' => ['required', 'integer', 'min:0'],
            'referral.credits_reward' => ['required', 'integer', 'min:0', 'max:1000'],
            'credits.actions.review_link' => ['required', 'integer', 'min:0', 'max:100'],
            'credits.actions.qr_download' => ['required', 'integer', 'min:0', 'max:100'],
            'credits.actions.vanity_slug' => ['required', 'integer', 'min:0', 'max:100'],
            'credits.packs' => ['required', 'array', 'min:1'],
            'credits.packs.*.key' => ['required', 'string', 'max:40'],
            'credits.packs.*.name' => ['required', 'string', 'max:80'],
            'credits.packs.*.credits' => ['required', 'integer', 'min:1'],
            'credits.packs.*.price' => ['required', 'integer', 'min:0'],
        ];
    }
}
