<?php

namespace App\Http\Requests\Admin\Patrol;

use Illuminate\Foundation\Http\FormRequest;

class StartPatrolReviewRequest extends FormRequest
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
        return [];
    }
}
