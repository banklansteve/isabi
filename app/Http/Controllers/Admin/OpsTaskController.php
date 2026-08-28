<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Admin\OpsAttentionFeed;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OpsTaskController extends Controller
{
    public function __invoke(Request $request, OpsAttentionFeed $feed): Response
    {
        $user = $request->user();

        abort_unless($user?->isOperationsAdmin(), 403);

        return Inertia::render('Admin/Ops/Tasks', $feed->home($user));
    }
}
