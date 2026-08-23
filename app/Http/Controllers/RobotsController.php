<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Allow: /p/',
            'Allow: /artisans',
            'Allow: /how-it-works',
            'Allow: /faq',
            '',
            'Disallow: /dashboard',
            'Disallow: /work-log',
            'Disallow: /tokens',
            'Disallow: /credits',
            'Disallow: /referrals',
            'Disallow: /help',
            'Disallow: /profile',
            'Disallow: /my-page',
            'Disallow: /admin',
            'Disallow: /internal',
            'Disallow: /r/',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /forgot-password',
            'Disallow: /reset-password',
            'Disallow: /verify-email',
            'Disallow: /confirm-password',
            '',
            'Sitemap: '.url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
