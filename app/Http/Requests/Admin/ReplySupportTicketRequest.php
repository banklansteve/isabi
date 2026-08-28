<?php

namespace App\Http\Requests\Admin;

use App\Models\SupportTicket;
use Illuminate\Foundation\Http\FormRequest;

class ReplySupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canDo('admin.support.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('body') === '') {
            $this->merge(['body' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxKb = (int) config('support.max_attachment_kb', 8192);

        return [
            'body' => ['nullable', 'string', 'max:5000', 'required_without:attachment'],
            'attachment' => ['nullable', 'file', 'max:'.$maxKb, 'mimes:jpg,jpeg,png,webp,gif,pdf'],
        ];
    }

    public function ticket(): SupportTicket
    {
        return $this->route('ticket');
    }
}
