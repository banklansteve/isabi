<?php

namespace App\Http\Requests\Admin;

use App\Support\Admin\DateRange;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterOpsInsightsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStaff() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $queues = array_merge(['all'], array_keys(config('admin.ops_insights.queues', [])));

        return [
            'range' => ['nullable', 'string', Rule::in(DateRange::presetIds())],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'queue' => ['nullable', 'string', Rule::in($queues)],
            'q' => ['nullable', 'string', 'max:80'],
            'sort' => ['nullable', 'string', Rule::in(['score', 'name', 'completed', 'actions', 'away', 'open'])],
        ];
    }

    /**
     * @return array{range: string, from: string|null, to: string|null, queue: string, q: string, sort: string}
     */
    public function filters(): array
    {
        $data = $this->validated();

        return [
            'range' => (string) ($data['range'] ?? 'this_week'),
            'from' => $data['from'] ?? null,
            'to' => $data['to'] ?? null,
            'queue' => (string) ($data['queue'] ?? 'all'),
            'q' => trim((string) ($data['q'] ?? '')),
            'sort' => (string) ($data['sort'] ?? 'score'),
        ];
    }
}
