<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $tab = $request->string('tab')->toString() === 'password' ? 'password' : 'profile';

        return Inertia::render('Admin/Ops/Account', [
            'tab' => $tab,
        ]);
    }
}
