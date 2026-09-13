<?php

namespace App\Http\Requests;

use App\Models\QuoteRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexQuotePipelineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $tabs = ['needs_response', 'awaiting_client', 'accepted', 'closed', 'all'];
        $statuses = [
            QuoteRequest::STATUS_NEW,
            QuoteRequest::STATUS_DRAFT,
            QuoteRequest::STATUS_AWAITING_CLIENT,
            QuoteRequest::STATUS_ACCEPTED,
            QuoteRequest::STATUS_DECLINED,
            QuoteRequest::STATUS_EXPIRED,
        ];
        $focuses = ['ready_to_log', 'expiring'];
        $sorts = ['newest', 'oldest', 'name', 'amount_high', 'amount_low', 'expiry'];

        $tab = (string) $this->query('tab', 'all');
        $status = (string) $this->query('status', '');
        $focus = (string) $this->query('focus', '');
        $sort = (string) $this->query('sort', 'newest');
        $q = trim((string) $this->query('q', ''));

        $this->merge([
            'q' => mb_substr($q, 0, 120),
            'tab' => in_array($tab, $tabs, true) ? $tab : 'all',
            'status' => in_array($status, $statuses, true) ? $status : '',
            'focus' => in_array($focus, $focuses, true) ? $focus : '',
            'sort' => in_array($sort, $sorts, true) ? $sort : 'newest',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'tab' => ['nullable', 'string', Rule::in([
                'needs_response',
                'awaiting_client',
                'accepted',
                'closed',
                'all',
            ])],
            'status' => ['nullable', 'string', Rule::in([
                '',
                QuoteRequest::STATUS_NEW,
                QuoteRequest::STATUS_DRAFT,
                QuoteRequest::STATUS_AWAITING_CLIENT,
                QuoteRequest::STATUS_ACCEPTED,
                QuoteRequest::STATUS_DECLINED,
                QuoteRequest::STATUS_EXPIRED,
            ])],
            'focus' => ['nullable', 'string', Rule::in(['', 'ready_to_log', 'expiring'])],
            'sort' => ['nullable', 'string', Rule::in([
                'newest',
                'oldest',
                'name',
                'amount_high',
                'amount_low',
                'expiry',
            ])],
        ];
    }

    /**
     * @return array{q: string, tab: string, status: string, focus: string, sort: string}
     */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'q' => trim((string) ($validated['q'] ?? '')),
            'tab' => (string) ($validated['tab'] ?? 'all'),
            'status' => (string) ($validated['status'] ?? ''),
            'focus' => (string) ($validated['focus'] ?? ''),
            'sort' => (string) ($validated['sort'] ?? 'newest'),
        ];
    }
}
