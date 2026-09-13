<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DestroyCareerVacancyRequest;
use App\Http\Requests\Admin\StoreCareerVacancyRequest;
use App\Http\Requests\Admin\UpdateCareerVacancyRequest;
use App\Models\CareerVacancy;
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
            ->orderByDesc('is_published')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get()
            ->map(fn (CareerVacancy $vacancy) => $vacancy->toAdminArray())
            ->values();

        return Inertia::render('Admin/Careers/Index', [
            'vacancies' => $vacancies,
            'published_count' => CareerVacancy::query()->where('is_published', true)->count(),
        ]);
    }

    public function store(StoreCareerVacancyRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $data['sort_order'] = $data['sort_order'] ?? ((int) CareerVacancy::query()->max('sort_order') + 10);
        $data['is_published'] = $data['is_published'] ?? false;
        $data['published_at'] = ! empty($data['is_published']) ? Carbon::now() : null;

        $vacancy = CareerVacancy::query()->create($data);

        AdminAudit::record(
            'career_vacancy.created',
            "{$request->user()->name} created vacancy: {$vacancy->title}",
            $vacancy,
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Vacancy created',
            'message' => $vacancy->is_published
                ? 'It is live on the public Careers page.'
                : 'Saved as a draft — publish when ready.',
        ], ['vacancy' => $vacancy->toAdminArray()]);
    }

    public function update(UpdateCareerVacancyRequest $request, CareerVacancy $vacancy): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $old = $vacancy->only([
            'title', 'department', 'location', 'employment_type', 'summary',
            'description', 'apply_email', 'apply_url', 'sort_order', 'is_published',
        ]);

        $wasPublished = $vacancy->is_published;
        $willPublish = array_key_exists('is_published', $data) ? (bool) $data['is_published'] : $wasPublished;

        if ($willPublish && ! $wasPublished) {
            $data['published_at'] = Carbon::now();
        }
        if (! $willPublish) {
            $data['published_at'] = null;
        }

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
        ], ['vacancy' => $vacancy->fresh()->toAdminArray()]);
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
}
