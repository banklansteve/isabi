<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\AuthorizesStaffManagement;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffShiftRequest extends FormRequest
{
    use AuthorizesStaffManagement;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'shift_days' => ['required', 'array', 'min:1'],
            'shift_days.*' => ['integer', 'in:1,2,3,4,5,6,7'],
            'shift_starts_at' => ['required', 'date_format:H:i'],
            'shift_ends_at' => ['required', 'date_format:H:i', 'different:shift_starts_at'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'shift_days.required' => 'Pick at least one working day.',
            'shift_days.min' => 'Pick at least one working day.',
            'shift_starts_at.required' => 'Set when their shift starts.',
            'shift_ends_at.required' => 'Set when their shift ends.',
            'shift_ends_at.different' => 'End time must be different from start time.',
        ];
    }

    /**
     * @return array{shift_days: list<int>, shift_starts_at: string, shift_ends_at: string}
     */
    public function shift(): array
    {
        $days = collect($this->validated('shift_days'))
            ->map(fn ($day) => (int) $day)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return [
            'shift_days' => $days,
            'shift_starts_at' => $this->validated('shift_starts_at'),
            'shift_ends_at' => $this->validated('shift_ends_at'),
        ];
    }
}
