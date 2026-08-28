<?php

namespace App\Http\Requests\Admin\Patrol;

use App\Models\PatrolCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecommendPatrolOutcomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canDo('patrol.investigate') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $case = $this->route('patrolCase');
        $allowed = $case instanceof PatrolCase
            ? array_keys($case->recommendationOptions())
            : array_keys(config('patrol.recommendations', []));

        return [
            'outcome' => ['required', 'string', Rule::in($allowed)],
            'reason' => ['required', 'string', 'max:4000'],
        ];
    }
}
