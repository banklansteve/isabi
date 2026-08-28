<?php

namespace App\Http\Requests\Chat;

use App\Support\Chat\MessageReactionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ToggleMessageReactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'emoji' => ['required', 'string', 'max:32', Rule::in(MessageReactionService::QUICK)],
        ];
    }
}
