<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TagSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canDo('admin.support.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $keys = collect(config('support.starters', []))
            ->pluck('key')
            ->reject(fn ($key) => $key === 'other')
            ->all();

        return [
            'tags' => ['present', 'array'],
            'tags.*' => ['string', Rule::in($keys)],
        ];
    }
}
