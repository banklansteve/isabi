<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DestroyFaqRequest;
use App\Http\Requests\Admin\StoreFaqRequest;
use App\Http\Requests\Admin\UpdateFaqRequest;
use App\Models\Faq;
use App\Support\Admin\AdminAudit;
use App\Support\Admin\AdminResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FaqAdminController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $faqs = Faq::query()
            ->orderByDesc('is_featured')
            ->orderByRaw('featured_sort is null')
            ->orderBy('featured_sort')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Faq $faq) => $faq->toAdminArray())
            ->values();

        return Inertia::render('Admin/Faqs/Index', [
            'faqs' => $faqs,
            'categories' => Faq::query()
                ->select('category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category')
                ->values(),
            'featured_count' => Faq::query()->where('is_featured', true)->where('is_published', true)->count(),
        ]);
    }

    public function store(StoreFaqRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $data['sort_order'] = $data['sort_order'] ?? ((int) Faq::query()->max('sort_order') + 10);
        $data['is_published'] = $data['is_published'] ?? true;
        $data['is_featured'] = $data['is_featured'] ?? false;

        if (! empty($data['is_featured']) && empty($data['featured_sort'])) {
            $data['featured_sort'] = ((int) Faq::query()->where('is_featured', true)->max('featured_sort')) + 1;
        }

        if (empty($data['is_featured'])) {
            $data['featured_sort'] = null;
        }

        $faq = Faq::query()->create($data);

        AdminAudit::record(
            'faq.created',
            "{$request->user()->name} created FAQ: {$faq->question}",
            $faq,
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'FAQ created',
            'message' => 'It is ready on the public FAQ page.',
        ], ['faq' => $faq->toAdminArray()]);
    }

    public function update(UpdateFaqRequest $request, Faq $faq): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $old = $faq->only(['question', 'answer', 'category', 'is_published', 'is_featured', 'featured_sort', 'sort_order']);

        if (array_key_exists('is_featured', $data) && $data['is_featured'] && empty($data['featured_sort']) && ! $faq->featured_sort) {
            $data['featured_sort'] = ((int) Faq::query()->where('is_featured', true)->max('featured_sort')) + 1;
        }

        if (array_key_exists('is_featured', $data) && ! $data['is_featured']) {
            $data['featured_sort'] = null;
        }

        $faq->fill($data)->save();

        AdminAudit::record(
            'faq.updated',
            "{$request->user()->name} updated FAQ: {$faq->question}",
            $faq,
            $old,
            $faq->only(['question', 'answer', 'category', 'is_published', 'is_featured', 'featured_sort', 'sort_order']),
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'FAQ updated',
            'message' => 'Changes are live for visitors.',
        ], ['faq' => $faq->fresh()->toAdminArray()]);
    }

    public function destroy(DestroyFaqRequest $request, Faq $faq): JsonResponse|RedirectResponse
    {
        $question = $faq->question;
        $id = $faq->id;
        $faq->delete();

        AdminAudit::record(
            'faq.destroyed',
            "{$request->user()->name} deleted FAQ: {$question}",
            null,
            ['id' => $id, 'question' => $question],
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'FAQ deleted',
            'message' => 'Removed from the public FAQ page.',
        ], ['deleted_id' => $id]);
    }
}
