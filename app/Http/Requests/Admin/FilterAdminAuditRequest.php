<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class FilterAdminAuditRequest extends FormRequest
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
            'q' => ['nullable', 'string', 'max:120'],
            'action' => ['nullable', 'string', 'max:80'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array{q: string, action: string, from: string, to: string, today: string}
     */
    public function filters(): array
    {
        $tz = (string) config('app.display_timezone', 'Africa/Lagos');
        $today = now($tz)->toDateString();
        $data = $this->validated();

        return [
            'q' => trim((string) ($data['q'] ?? '')),
            'action' => (string) ($data['action'] ?? ''),
            'from' => $data['from'] ?? $today,
            'to' => $data['to'] ?? $today,
            'today' => $today,
        ];
    }
}
