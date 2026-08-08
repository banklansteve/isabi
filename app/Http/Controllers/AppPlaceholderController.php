<?php

namespace App\Http\Controllers;

use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppPlaceholderController extends Controller
{
    public function myPage(Request $request): \Illuminate\Http\RedirectResponse
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

    public function referrals(Request $request): Response
    {
        $user = $request->user();

        ActivityLogger::log(
            action: 'page.referrals',
            summary: "{$user->name} opened Referrals.",
            user: $user,
        );

        return Inertia::render('App/Placeholder', [
            'title' => 'Referrals',
            'eyebrow' => 'Grow together',
            'summary' => 'Share your referral link or code. Earn credits when artisans you invite log their first job.',
            'icon' => 'ti ti-gift',
            'highlights' => [
                'Your referral link & code',
                'Credits earned so far',
                'Who signed up through you',
            ],
        ]);
    }

    public function help(Request $request): Response
    {
        $user = $request->user();

        ActivityLogger::log(
            action: 'page.help',
            summary: "{$user->name} opened Help & support.",
            user: $user,
        );

        return Inertia::render('App/Placeholder', [
            'title' => 'Help & support',
            'eyebrow' => 'Support',
            'summary' => 'Guides, FAQs, and a way to reach the Isabi team when something isn’t clear.',
            'icon' => 'ti ti-help-circle',
            'highlights' => [
                'How Isabi works',
                'Billing questions',
                'Contact support',
            ],
            'cta' => [
                'label' => 'Read the FAQ',
                'href' => route('faq'),
            ],
        ]);
    }
}
