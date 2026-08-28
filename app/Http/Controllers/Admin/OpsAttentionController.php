<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MarkOpsAttentionReadRequest;
use App\Support\Admin\OpsAttentionFeed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OpsAttentionController extends Controller
{
    public function __construct(private readonly OpsAttentionFeed $feed) {}

    public function read(MarkOpsAttentionReadRequest $request): RedirectResponse|Response
    {
        $data = $request->validated();
        $this->feed->markRead($request->user(), $data['key'], $data['signature']);

        if (! $request->header('X-Inertia')) {
            return response()->noContent();
        }

        return back();
    }

    public function readAll(Request $request): RedirectResponse|Response
    {
        $user = $request->user();

        abort_unless($user?->isOperationsAdmin() && ! $user->isRestrictedStaff(), 403);

        $this->feed->markAllRead($user);

        if (! $request->header('X-Inertia')) {
            return response()->noContent();
        }

        return back();
    }
}
