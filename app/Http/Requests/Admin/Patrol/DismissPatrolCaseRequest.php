<?php

namespace App\Http\Requests\Admin\Patrol;

use App\Models\PatrolCase;
use App\Support\Patrol\PatrolCaseService;
use Illuminate\Foundation\Http\FormRequest;

class DismissPatrolCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $case = $this->route('patrolCase');

        if (! $user || ! $case instanceof PatrolCase) {
            return false;
        }

        return app(PatrolCaseService::class)->canDismiss($user, $case);
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
