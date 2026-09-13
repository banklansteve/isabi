<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DestroyCareerVacancyRequest;
use App\Http\Requests\Admin\StoreCareerVacancyRequest;
use App\Http\Requests\Admin\UpdateCareerVacancyRequest;
use App\Models\CareerVacancy;
use App\Models\HrProfile;
use App\Models\StaffRole;
use App\Models\User;
use App\Support\Admin\AdminAudit;
use App\Support\Admin\AdminResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class CareerVacancyAdminController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $vacancies = CareerVacancy::query()
            ->with(['hiringManager:id,name', 'staffRole:id,name'])
            ->withCount('applications')
            ->orderByRaw("CASE status WHEN 'open' THEN 0 WHEN 'draft' THEN 1 WHEN 'paused' THEN 2 WHEN 'closed' THEN 3 WHEN 'filled' THEN 4 ELSE 5 END")
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get()
            ->map(fn (CareerVacancy $vacancy) => $vacancy->toAdminArray())
            ->values();

        return Inertia::render('Admin/Careers/Index', [
            'vacancies' => $vacancies,
            'open_count' => CareerVacancy::query()->where('status', 'open')->count(),
            'meta' => $this->formMeta(),
        ]);
    }

    public function store(StoreCareerVacancyRequest $request): JsonResponse|RedirectResponse
    {
        $data = $this->preparePayload($request->validated());
        $data['sort_order'] = $data['sort_order'] ?? ((int) CareerVacancy::query()->max('sort_order') + 10);

        $vacancy = CareerVacancy::query()->create($data);

        AdminAudit::record(
            'career_vacancy.created',
            "{$request->user()->name} created vacancy: {$vacancy->title}",
            $vacancy,
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Vacancy created',
            'message' => $vacancy->status === 'open' && $vacancy->is_public
                ? 'It is live on the public Careers page.'
                : 'Saved — set status to Open and public visibility when ready.',
        ], ['vacancy' => $vacancy->load(['hiringManager:id,name', 'staffRole:id,name'])->loadCount('applications')->toAdminArray()]);
    }

    public function update(UpdateCareerVacancyRequest $request, CareerVacancy $vacancy): JsonResponse|RedirectResponse
    {
        $data = $this->preparePayload($request->validated(), $vacancy);
        $old = $vacancy->only([
            'title', 'department', 'status', 'is_public', 'closes_at', 'openings',
        ]);

        $vacancy->fill($data)->save();

        AdminAudit::record(
            'career_vacancy.updated',
            "{$request->user()->name} updated vacancy: {$vacancy->title}",
            $vacancy,
            $old,
            $vacancy->only(array_keys($old)),
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Vacancy updated',
            'message' => 'Changes saved.',
        ], [
            'vacancy' => $vacancy->fresh()
                ->load(['hiringManager:id,name', 'staffRole:id,name'])
                ->loadCount('applications')
                ->toAdminArray(),
        ]);
    }

    public function destroy(DestroyCareerVacancyRequest $request, CareerVacancy $vacancy): JsonResponse|RedirectResponse
    {
        $title = $vacancy->title;
        $id = $vacancy->id;
        $vacancy->delete();

        AdminAudit::record(
            'career_vacancy.destroyed',
            "{$request->user()->name} deleted vacancy: {$title}",
            null,
            ['id' => $id, 'title' => $title],
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Vacancy deleted',
            'message' => 'Removed from Careers.',
        ], ['deleted_id' => $id]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function preparePayload(array $data, ?CareerVacancy $existing = null): array
    {
        $data['salary_is_public'] = (bool) ($data['salary_is_public'] ?? false);
        $data['is_public'] = (bool) ($data['is_public'] ?? false);
        $data['salary_currency'] = $data['salary_currency'] ?? 'NGN';
        $data['apply_url'] = $data['apply_url'] ?: null;
        $data['apply_email'] = $data['apply_email'] ?: null;
        $data['hiring_manager_id'] = $data['hiring_manager_id'] ?: null;
        $data['staff_role_id'] = $data['staff_role_id'] ?: null;
        $data['salary_min'] = $data['salary_min'] ?: null;
        $data['salary_max'] = $data['salary_max'] ?: null;

        $status = $data['status'] ?? $existing?->status ?? 'draft';
        $wasOpenPublic = $existing && $existing->status === 'open' && $existing->is_public;
        $willOpenPublic = $status === 'open' && ! empty($data['is_public']);

        // Keep legacy is_published in sync for any leftover readers.
        $data['is_published'] = $willOpenPublic;

        if ($willOpenPublic && ! $wasOpenPublic) {
            $data['published_at'] = ! empty($data['published_at'])
                ? Carbon::parse($data['published_at'])
                : ($existing?->published_at ?? Carbon::now());
        } elseif (! empty($data['published_at'])) {
            $data['published_at'] = Carbon::parse($data['published_at']);
        } elseif ($existing?->published_at) {
            // keep existing unless cleared
        } else {
            $data['published_at'] = null;
        }

        if (! empty($data['closes_at'])) {
            $data['closes_at'] = Carbon::parse($data['closes_at'])->toDateString();
        } else {
            $data['closes_at'] = null;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function formMeta(): array
    {
        $departments = HrProfile::query()
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department')
            ->values()
            ->all();

        $managers = User::query()
            ->staff()
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $u) => [
                'value' => $u->id,
                'label' => $u->name.($u->email ? " ({$u->email})" : ''),
            ])
            ->values()
            ->all();

        $roles = StaffRole::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (StaffRole $r) => [
                'value' => $r->id,
                'label' => $r->name,
            ])
            ->values()
            ->all();

        return [
            'departments' => $departments,
            'hiring_managers' => $managers,
            'staff_roles' => $roles,
            'statuses' => collect(CareerVacancy::STATUSES)->map(fn ($v) => [
                'value' => $v,
                'label' => ucfirst($v),
            ])->values()->all(),
            'employment_types' => [
                ['value' => 'full-time', 'label' => 'Full-time'],
                ['value' => 'part-time', 'label' => 'Part-time'],
                ['value' => 'contract', 'label' => 'Contract'],
                ['value' => 'internship', 'label' => 'Internship'],
            ],
            'work_modes' => [
                ['value' => 'remote', 'label' => 'Remote'],
                ['value' => 'hybrid', 'label' => 'Hybrid'],
                ['value' => 'onsite', 'label' => 'Onsite'],
            ],
        ];
    }
}
