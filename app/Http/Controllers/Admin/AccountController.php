<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdminAccountNameRequest;
use App\Services\CloudinaryMediaService;
use App\Support\ActivityLogger;
use App\Support\Admin\AdminResponse;
use App\Support\Admin\OpsDutyPresenter;
use App\Support\Staff\StaffPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class AccountController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('Admin/Ops/Account', $this->pageProps($request, 'profile'));
    }

    public function updateAvatar(Request $request, CloudinaryMediaService $cloudinary): JsonResponse|RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:5120'],
        ]);

        $user = $request->user();

        try {
            $uploaded = $cloudinary->uploadProfilePhoto($request->file('avatar'), $user->id);
        } catch (RuntimeException $e) {
            return AdminResponse::mutation($request, [
                'type' => 'error',
                'title' => 'Upload failed',
                'message' => $e->getMessage(),
            ]);
        }

        if (filled($user->avatar_path)) {
            try {
                $cloudinary->delete($user->avatar_path);
            } catch (RuntimeException) {
                // Ignore cleanup failures — new photo still wins.
            }
        }

        $user->forceFill([
            'avatar_path' => $uploaded['public_id'] ?? $user->avatar_path,
            'avatar_url' => $uploaded['url'] ?? $user->avatar_url,
        ])->save();

        ActivityLogger::log(
            action: 'profile.avatar_updated',
            summary: "{$user->name} updated their admin profile photo.",
            user: $user,
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Photo updated',
            'message' => 'Your profile photo is updated across the console.',
        ], [
            'account' => $this->accountPayload($user->fresh()),
        ]);
    }

    public function updateProfile(UpdateAdminAccountNameRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $names = $request->names();

        $user->forceFill($names)->save();

        ActivityLogger::log(
            action: 'profile.name_updated',
            summary: "{$user->name} updated their admin profile name.",
            user: $user,
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Name updated',
            'message' => 'Your name is updated across the console.',
        ], [
            'account' => $this->accountPayload($user->fresh()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function pageProps(Request $request, string $tab): array
    {
        $tab = $request->string('tab')->toString() === 'password' ? 'password' : 'profile';

        return [
            'tab' => $tab,
            'account' => $this->accountPayload($request->user()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function accountPayload(\App\Models\User $user): array
    {
        $user->loadMissing('staffRoles');
        $tz = (string) config('app.display_timezone', config('app.timezone'));
        $roles = StaffPresenter::rolesPayload($user)['roles'];
        $duties = OpsDutyPresenter::badges($user);

        return [
            'name' => $user->name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'uid' => $user->uid,
            'avatar_url' => $user->avatar_url,
            'user_type' => $user->role?->value,
            'user_type_label' => $user->role?->label(),
            'user_type_description' => $user->role?->description(),
            'staff_status' => $user->staff_status?->value,
            'staff_status_label' => $user->staff_status?->label(),
            'is_super_admin' => $user->isSuperAdmin(),
            'roles' => $roles,
            'duties' => $duties,
            'role_summary' => OpsDutyPresenter::summary($duties),
            'joined' => $user->created_at?->timezone($tz)->format('j M Y'),
            'last_login' => $user->last_login_at?->timezone($tz)->format('j M Y · g:ia'),
            'initials' => strtoupper(mb_substr((string) $user->first_name, 0, 1).mb_substr((string) $user->last_name, 0, 1))
                ?: strtoupper(mb_substr($user->name, 0, 2)),
        ];
    }
}
