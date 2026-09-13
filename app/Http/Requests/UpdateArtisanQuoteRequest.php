<?php

namespace App\Http\Requests;

use App\Support\Quotes\QuoteLineItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateArtisanQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $quoteRequest = $this->route('quoteRequest');

        return $quoteRequest
            && (int) $quoteRequest->user_id === (int) $this->user()?->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'valid_until' => ['required', 'date', 'after:today'],
            'estimated_start' => ['nullable', 'date', 'after_or_equal:today'],
            'estimated_duration_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'scope_of_work' => ['nullable', 'string', 'max:5000'],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.kind' => ['required', 'string', Rule::in([
                QuoteLineItem::KIND_LABOUR,
                QuoteLineItem::KIND_MATERIALS,
                QuoteLineItem::KIND_CALLOUT,
                QuoteLineItem::KIND_TRANSPORT,
                QuoteLineItem::KIND_INSPECTION,
                QuoteLineItem::KIND_PERMIT,
                QuoteLineItem::KIND_OTHER,
            ])],
            'line_items.*.label' => ['nullable', 'string', 'max:500'],
            'line_items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.unit' => ['nullable', 'string', 'max:40'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'terms' => ['nullable', 'string', 'max:2000'],
            'payment_terms' => ['nullable', 'string', 'max:500'],
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_naira' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->input('line_items', []) as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }

                $kind = (string) ($row['kind'] ?? '');

                if ($kind === QuoteLineItem::KIND_MATERIALS && (float) ($row['unit_price'] ?? 0) > 0) {
                    if (blank($row['label'] ?? null)) {
                        $validator->errors()->add(
                            "line_items.{$index}.label",
                            'Enter the material or part name.',
                        );
                    }

                    if ((float) ($row['quantity'] ?? 0) <= 0) {
                        $validator->errors()->add(
                            "line_items.{$index}.quantity",
                            'Enter the number of units.',
                        );
                    }
                }

                if ($kind === QuoteLineItem::KIND_OTHER && blank($row['label'] ?? null)) {
                    $validator->errors()->add(
                        "line_items.{$index}.label",
                        'Describe this charge.',
                    );
                }
            }
        });
    }
}
