<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DestroyJobCategoryRequest;
use App\Http\Requests\Admin\DestroyJobSubcategoryRequest;
use App\Http\Requests\Admin\DestroySkillTagRequest;
use App\Http\Requests\Admin\StoreJobCategoryRequest;
use App\Http\Requests\Admin\StoreJobSubcategoryRequest;
use App\Http\Requests\Admin\StoreSkillTagRequest;
use App\Http\Requests\Admin\UpdateJobCategoryRequest;
use App\Http\Requests\Admin\UpdateJobSubcategoryRequest;
use App\Http\Requests\Admin\UpdateSkillTagRequest;
use App\Models\JobCategory;
use App\Models\JobSubcategory;
use App\Models\SkillTag;
use App\Support\Admin\AdminAudit;
use App\Support\Admin\AdminResponse;
use App\Support\JobCategories;
use App\Support\SkillsCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaxonomyAdminController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $categories = JobCategory::query()
            ->with(['subcategories' => fn ($q) => $q->orderBy('sort_order')->orderBy('name')])
            ->withCount('subcategories')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (JobCategory $c) => $c->toAdminArray())
            ->values();

        $skills = SkillTag::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (SkillTag $s) => $s->toAdminArray())
            ->values();

        return Inertia::render('Admin/Taxonomy/Index', [
            'categories' => $categories,
            'skills' => $skills,
        ]);
    }

    public function storeCategory(StoreJobCategoryRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $category = JobCategory::query()->create([
            'name' => $data['name'],
            'sort_order' => $data['sort_order'] ?? ((int) JobCategory::query()->max('sort_order') + 10),
            'is_active' => $data['is_active'] ?? true,
        ]);

        JobCategories::forgetCache();

        AdminAudit::record('taxonomy.category_created', "{$request->user()->name} created job category {$category->name}", $category);

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Category created',
            'message' => $category->name.' is ready for subcategories.',
        ], ['category' => $category->load('subcategories')->loadCount('subcategories')->toAdminArray()]);
    }

    public function updateCategory(UpdateJobCategoryRequest $request, JobCategory $category): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $old = $category->only(['name', 'sort_order', 'is_active']);
        $category->fill($data)->save();
        JobCategories::forgetCache();

        AdminAudit::record('taxonomy.category_updated', "{$request->user()->name} updated job category {$category->name}", $category, $old, $category->only(['name', 'sort_order', 'is_active']));

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Category updated',
            'message' => 'Signup and job forms will use the new label.',
        ], ['category' => $category->load('subcategories')->loadCount('subcategories')->toAdminArray()]);
    }

    public function destroyCategory(DestroyJobCategoryRequest $request, JobCategory $category): JsonResponse|RedirectResponse
    {
        $name = $category->name;
        $id = $category->id;
        $category->delete();
        JobCategories::forgetCache();

        AdminAudit::record('taxonomy.category_destroyed', "{$request->user()->name} deleted job category {$name}", null, ['id' => $id, 'name' => $name]);

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Category deleted',
            'message' => 'Subcategories under it were removed too.',
        ], ['deleted_id' => $id]);
    }

    public function storeSubcategory(StoreJobSubcategoryRequest $request, JobCategory $category): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $sub = $category->subcategories()->create([
            'name' => $data['name'],
            'review_phrase' => $data['review_phrase'] ?? null,
            'sort_order' => $data['sort_order'] ?? ((int) $category->subcategories()->max('sort_order') + 10),
            'is_active' => $data['is_active'] ?? true,
        ]);

        JobCategories::forgetCache();

        AdminAudit::record('taxonomy.subcategory_created', "{$request->user()->name} added subcategory {$sub->name} under {$category->name}", $sub);

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Subcategory added',
            'message' => $sub->name.' will show at signup under '.$category->name.'.',
        ], [
            'subcategory' => $sub->toAdminArray(),
            'category_id' => $category->id,
        ]);
    }

    public function updateSubcategory(UpdateJobSubcategoryRequest $request, JobSubcategory $subcategory): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $old = $subcategory->only(['name', 'review_phrase', 'sort_order', 'is_active', 'job_category_id']);
        $subcategory->fill($data)->save();
        JobCategories::forgetCache();

        AdminAudit::record('taxonomy.subcategory_updated', "{$request->user()->name} updated subcategory {$subcategory->name}", $subcategory, $old, $subcategory->only(['name', 'review_phrase', 'sort_order', 'is_active', 'job_category_id']));

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Subcategory updated',
            'message' => 'Changes are live for artisans.',
        ], [
            'subcategory' => $subcategory->fresh()->toAdminArray(),
            'category_id' => $subcategory->job_category_id,
        ]);
    }

    public function destroySubcategory(DestroyJobSubcategoryRequest $request, JobSubcategory $subcategory): JsonResponse|RedirectResponse
    {
        $name = $subcategory->name;
        $id = $subcategory->id;
        $categoryId = $subcategory->job_category_id;
        $subcategory->delete();
        JobCategories::forgetCache();

        AdminAudit::record('taxonomy.subcategory_destroyed', "{$request->user()->name} deleted subcategory {$name}", null, ['id' => $id, 'name' => $name]);

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Subcategory deleted',
            'message' => $name.' was removed.',
        ], ['deleted_id' => $id, 'category_id' => $categoryId]);
    }

    public function storeSkill(StoreSkillTagRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $skill = SkillTag::query()->create([
            'name' => $data['name'],
            'sort_order' => $data['sort_order'] ?? ((int) SkillTag::query()->max('sort_order') + 10),
            'is_active' => $data['is_active'] ?? true,
        ]);

        SkillsCatalog::forgetCache();

        AdminAudit::record('taxonomy.skill_created', "{$request->user()->name} created skill {$skill->name}", $skill);

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Skill added',
            'message' => $skill->name.' is available as a suggestion.',
        ], ['skill' => $skill->toAdminArray()]);
    }

    public function updateSkill(UpdateSkillTagRequest $request, SkillTag $skill): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $old = $skill->only(['name', 'sort_order', 'is_active']);
        $skill->fill($data)->save();
        SkillsCatalog::forgetCache();

        AdminAudit::record('taxonomy.skill_updated', "{$request->user()->name} updated skill {$skill->name}", $skill, $old, $skill->only(['name', 'sort_order', 'is_active']));

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Skill updated',
            'message' => 'Profile skill suggestions refreshed.',
        ], ['skill' => $skill->fresh()->toAdminArray()]);
    }

    public function destroySkill(DestroySkillTagRequest $request, SkillTag $skill): JsonResponse|RedirectResponse
    {
        $name = $skill->name;
        $id = $skill->id;
        $skill->delete();
        SkillsCatalog::forgetCache();

        AdminAudit::record('taxonomy.skill_destroyed', "{$request->user()->name} deleted skill {$name}", null, ['id' => $id, 'name' => $name]);

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Skill deleted',
            'message' => $name.' was removed from suggestions.',
        ], ['deleted_id' => $id]);
    }
}
