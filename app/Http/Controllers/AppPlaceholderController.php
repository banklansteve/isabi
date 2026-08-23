<?php

namespace App\Http\Controllers;

use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppPlaceholderController extends Controller
{
    public function myPage(Request $request): RedirectResponse
    {
        $user = $request->user();

        ActivityLogger::log(
            action: 'page.my_page',
            summary: "{$user->name} opened My page (public profile).",
            user: $user,
        );

        if (filled($user->slug)) {
            return redirect()->to('/p/'.$user->slug);
        }

        return redirect()->route('profile.edit')->with('toast', [
            'type' => 'info',
            'message' => 'Set your business name to get a public page URL.',
            'duration' => 4500,
        ]);
    }

    public function workLog(Request $request): Response
    {
        $user = $request->user();

        ActivityLogger::log(
            action: 'page.work_log',
            summary: "{$user->name} opened Work log.",
            user: $user,
        );

        return Inertia::render('App/Placeholder', [
            'title' => 'Work log',
            'eyebrow' => 'Jobs',
            'summary' => 'Log finished jobs in under a minute, then send a WhatsApp review link. Past entries will live here.',
            'icon' => 'ti ti-notebook',
            'highlights' => [
                'Add a new job entry',
                'Browse past work',
                'Send client review links',
            ],
        ]);
    }

    public function credits(Request $request): Response
    {
        $user = $request->user();

        ActivityLogger::log(
            action: 'page.credits',
            summary: "{$user->name} opened Credits & plan.",
            user: $user,
        );

        return Inertia::render('App/Placeholder', [
            'title' => 'Credits & plan',
            'eyebrow' => 'Billing',
            'summary' => 'See your credit balance, top up when you need more review links, and manage annual plan status or renewal.',
            'icon' => 'ti ti-wallet',
            'highlights' => [
                'Credit balance & history',
                'Top-up packs',
                'Annual plan status',
            ],
        ]);
    }
}
