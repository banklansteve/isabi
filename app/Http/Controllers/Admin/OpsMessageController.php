<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DestroyOpsMessageTemplateRequest;
use App\Http\Requests\Admin\SendOpsTemplatedMessageRequest;
use App\Http\Requests\Admin\StoreOpsMessageTemplateRequest;
use App\Http\Requests\Admin\UpdateOpsMessageTemplateRequest;
use App\Models\OpsMessageTemplate;
use App\Models\User;
use App\Support\Admin\AdminAudit;
use App\Support\Admin\AdminResponse;
use App\Support\Admin\OpsTemplatedMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OpsMessageController extends Controller
{
    public function __construct(private readonly OpsTemplatedMessageService $messages) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless(
            $user?->canDo('admin.ops_messages.send')
                || $user?->canDo('admin.users.manage')
                || $user?->canDo('admin.messaging.manage'),
            403,
        );

        if (Schema::hasTable('ops_message_templates') && ! OpsMessageTemplate::query()->exists()) {
            $this->messages->seedDefaults($user);
        }

        $q = trim((string) $request->query('q', ''));
        $artisans = collect();

        if ($q !== '') {
            $artisans = User::query()
                ->artisans()
                ->where(function ($query) use ($q) {
                    $like = '%'.$q.'%';
                    $query->where('name', 'like', $like)
                        ->orWhere('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('business_name', 'like', $like)
                        ->orWhere('uid', 'like', $like);
                })
                ->orderBy('name')
                ->limit(30)
                ->get(['id', 'uid', 'name', 'first_name', 'last_name', 'email', 'business_name', 'avatar_url'])
                ->map(fn (User $artisan) => [
                    'id' => $artisan->id,
                    'uid' => $artisan->uid,
                    'name' => $artisan->name,
                    'email' => $artisan->email,
                    'business_name' => $artisan->business_name,
                    'avatar_url' => $artisan->avatar_url,
                ]);
        }

        return Inertia::render('Admin/OpsMessages/Index', [
            'q' => $q,
            'artisans' => $artisans->values()->all(),
            'templates' => $this->messages->activeTemplates()->map(fn (OpsMessageTemplate $template) => [
                'uid' => $template->uid,
                'title' => $template->title,
                'category' => $template->category,
                'subject' => $template->subject,
                'body' => $template->body,
                'editable_keys' => $template->editable_keys ?? ['body'],
            ])->values()->all(),
            'all_templates' => $user->isSuperAdmin()
                ? OpsMessageTemplate::query()->orderBy('title')->get()->map(fn (OpsMessageTemplate $template) => [
                    'uid' => $template->uid,
                    'title' => $template->title,
                    'category' => $template->category,
                    'subject' => $template->subject,
                    'body' => $template->body,
                    'editable_keys' => $template->editable_keys ?? ['body'],
                    'is_active' => $template->is_active,
                ])->values()->all()
                : [],
            'messaging_enabled' => $this->messages->messagingEnabled() || $user->isSuperAdmin(),
            'requires_approval' => $this->messages->requiresSendApproval($user),
            'is_super' => $user->isSuperAdmin(),
        ]);
    }

    public function send(SendOpsTemplatedMessageRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $artisan = User::query()->findOrFail((int) $data['user_id']);
        $template = OpsMessageTemplate::query()->where('uid', $data['template_uid'])->firstOrFail();

        $result = $this->messages->send($request->user(), $artisan, $template, [
            'subject' => $data['subject'] ?? null,
            'body' => $data['body'] ?? null,
            'channels' => $data['channels'],
        ]);

        if ($result['status'] === 'pending') {
            return AdminResponse::mutation($request, [
                'type' => 'success',
                'title' => 'Sent for approval',
                'message' => 'Super Admin will review before this reaches the user.',
            ]);
        }

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Message sent',
            'message' => 'Delivered via the selected channels.',
        ]);
    }

    public function storeTemplate(StoreOpsMessageTemplateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $template = OpsMessageTemplate::query()->create([
            'uid' => 'OT'.strtoupper(Str::random(10)),
            'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(4)),
            'title' => $data['title'],
            'category' => $data['category'] ?? 'general',
            'subject' => $data['subject'],
            'body' => $data['body'],
            'editable_keys' => $data['editable_keys'] ?? ['body'],
            'is_active' => true,
            'created_by_user_id' => $request->user()->id,
        ]);

        AdminAudit::record(
            'ops_templates.created',
            "{$request->user()->name} created message template {$template->title}.",
            $template,
        );

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Template saved',
            'message' => 'Operations can use it from User messages.',
        ]);
    }

    public function updateTemplate(UpdateOpsMessageTemplateRequest $request, OpsMessageTemplate $template): RedirectResponse
    {
        $data = $request->validated();
        $template->forceFill([
            'title' => $data['title'],
            'category' => $data['category'] ?? $template->category,
            'subject' => $data['subject'],
            'body' => $data['body'],
            'editable_keys' => $data['editable_keys'] ?? $template->editable_keys,
            'is_active' => (bool) ($data['is_active'] ?? $template->is_active),
        ])->save();

        AdminAudit::record(
            'ops_templates.updated',
            "{$request->user()->name} updated message template {$template->title}.",
            $template,
        );

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Template updated',
        ]);
    }

    public function destroyTemplate(DestroyOpsMessageTemplateRequest $request, OpsMessageTemplate $template): RedirectResponse
    {
        $title = $template->title;
        $template->delete();

        AdminAudit::record(
            'ops_templates.deleted',
            "{$request->user()->name} deleted message template {$title}.",
        );

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Template removed',
        ]);
    }
}
