<?php

namespace App\Http\Requests;

use App\Support\Tokens\TokenCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseTokenPackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $keys = collect(TokenCatalog::packs())->pluck('key')->all();

        return [
            'pack' => ['required', 'string', Rule::in($keys)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pack.in' => 'Choose a valid token pack.',
        ];
    }
}
