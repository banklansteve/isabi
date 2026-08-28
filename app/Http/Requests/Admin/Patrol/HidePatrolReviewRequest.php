<?php

namespace App\Http\Requests\Admin\Patrol;

use App\Models\PatrolCase;
use Illuminate\Foundation\Http\FormRequest;

class HidePatrolReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('patrolCase');

        return ($this->user()?->canDo('patrol.resolve') ?? false)
            && $case instanceof PatrolCase
            && $case->isReview();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:4000'],
        ];
    }
}
