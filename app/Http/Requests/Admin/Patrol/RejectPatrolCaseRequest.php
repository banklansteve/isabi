<?php

namespace App\Http\Requests\Admin\Patrol;

use Illuminate\Foundation\Http\FormRequest;

class RejectPatrolCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canDo('patrol.resolve') ?? false;
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
