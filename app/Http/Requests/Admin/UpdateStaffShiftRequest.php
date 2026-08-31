<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\AuthorizesStaffManagement;
use App\Support\Staff\StaffShift;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'shift_breaks' => ['nullable', 'array', 'max:5'],
            'shift_breaks.*.start' => ['required', 'date_format:H:i'],
            'shift_breaks.*.end' => ['required', 'date_format:H:i'],
            'shift_breaks.*.label' => ['nullable', 'string', 'max:40'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $start = StaffShift::normalizeTime((string) $this->input('shift_starts_at', '08:00'));
            $end = StaffShift::normalizeTime((string) $this->input('shift_ends_at', '18:00'));
            $breaks = StaffShift::normalizeBreaks($this->input('shift_breaks', []), $start, $end);

            foreach ($breaks as $index => $break) {
                if ($break['start'] >= $break['end']) {
                    $validator->errors()->add("shift_breaks.{$index}.end", 'Break end must be after the start.');

                    continue;
                }

                if ($break['start'] < $start || $break['end'] > $end) {
                    $validator->errors()->add("shift_breaks.{$index}.start", 'Break must fall within the shift hours.');
                }
            }

            for ($i = 1; $i < count($breaks); $i++) {
                if ($breaks[$i]['start'] < $breaks[$i - 1]['end']) {
                    $validator->errors()->add('shift_breaks', 'Break windows cannot overlap.');
                    break;
                }
            }
        });
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
     * @return array{shift_days: list<int>, shift_starts_at: string, shift_ends_at: string, shift_breaks: list<array{start: string, end: string, label: string}>}
     */
    public function shift(): array
    {
        $days = collect($this->validated('shift_days'))
            ->map(fn ($day) => (int) $day)
            ->unique()
            ->sort()
            ->values()
            ->all();
        $start = StaffShift::normalizeTime($this->validated('shift_starts_at'));
        $end = StaffShift::normalizeTime($this->validated('shift_ends_at'));

        return [
            'shift_days' => $days,
            'shift_starts_at' => $start,
            'shift_ends_at' => $end,
            'shift_breaks' => StaffShift::normalizeBreaks($this->validated('shift_breaks') ?? [], $start, $end),
        ];
    }
}
