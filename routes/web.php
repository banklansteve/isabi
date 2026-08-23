<?php

use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\AppPlaceholderController;
use App\Http\Controllers\ArtisanDirectoryController;
use App\Http\Controllers\CookieConsentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\Internal\PricingDocsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicJobController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\PublicReviewController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\WorkLogController;
use App\Support\Seo;
use App\Support\SeoSchema;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $copy = config('seo.pages.home');

    app(Seo::class)
        ->title($copy['title'])
        ->description($copy['description'])
        ->canonical(url('/'))
        ->schema(SeoSchema::organization())
        ->schema(SeoSchema::website());

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('home');

Route::get('/faq', function () {
    $copy = config('seo.pages.faq');

    app(Seo::class)
        ->title($copy['title'])
        ->description($copy['description'])
        ->canonical(route('faq'))
        ->schema(SeoSchema::faq(config('seo.faq')));

    return Inertia::render('Faq', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('faq');

Route::get('/how-it-works', function () {
    $copy = config('seo.pages.how-it-works');

    app(Seo::class)
        ->title($copy['title'])
        ->description($copy['description'])
        ->canonical(route('how-it-works'));

    return Inertia::render('HowItWorks', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('how-it-works');

Route::get('/goodbye', function () {
    app(Seo::class)->title('We’re sorry to see you go')->noindex();

    return Inertia::render('Account/Goodbye', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('account.goodbye');

$staticPages = [
    'about' => [
        'title' => 'About Isabi',
        'eyebrow' => 'Company',
        'summary' => 'Why we built a proof-of-work platform for skilled trades across Nigeria.',
        'body' => 'Isabi helps artisans turn finished jobs and real client reviews into a shareable track record — without self-written testimonials or pay-to-look-established shortcuts.',
    ],
    'contact' => [
        'title' => 'Contact',
        'eyebrow' => 'Support',
        'summary' => 'Questions about your page, billing, or partnerships? Reach the team.',
        'body' => 'Email hello@isabi.dev and we will get back to you. For urgent account issues, include the email you signed up with.',
    ],
    'careers' => [
        'title' => 'Careers',
        'eyebrow' => 'Company',
        'summary' => 'Help build trust infrastructure for millions of skilled workers.',
        'body' => 'We are not hiring in volume yet, but we always want to hear from people who care about products that work offline-first, mobile-first, and honesty-first. Write to hello@isabi.dev with “Careers” in the subject.',
    ],
    'terms' => [
        'title' => 'Terms of use',
        'eyebrow' => 'Legal',
        'summary' => 'The rules for using Isabi — for artisans, clients leaving reviews, and visitors.',
        'body' => 'This is a placeholder for our full terms. Until published, using Isabi means you agree to use the product lawfully, not to fabricate reviews, and not to misuse another person’s identity or work history.',
    ],
    'privacy' => [
        'title' => 'Privacy policy',
        'eyebrow' => 'Legal',
        'summary' => 'How we collect, store, and protect personal data on Isabi.',
        'body' => 'This is a placeholder for our full privacy policy. We collect account details, job logs, and review submissions to run the product. We do not sell your data. Payment card details are never stored for recurring billing.',
    ],
    'cookies' => [
        'title' => 'Cookie policy',
        'eyebrow' => 'Legal',
        'summary' => 'What cookies and similar technologies Isabi uses, and why.',
        'body' => 'This is a placeholder for our cookie policy. We use essential cookies for login sessions and security. Analytics cookies, if added later, will be documented here with clear opt-out options where required.',
    ],
    'acceptable-use' => [
        'title' => 'Acceptable use',
        'eyebrow' => 'Legal',
        'summary' => 'What you can and cannot do on Isabi — especially around reviews and impersonation.',
        'body' => 'This is a placeholder for our acceptable use policy. You may not coerce fake reviews, impersonate clients, harass others, or use Isabi to promote illegal services. Violations can lead to content removal or account suspension.',
    ],
];

foreach ($staticPages as $slug => $page) {
    Route::get('/'.$slug, function () use ($page) {
        app(Seo::class)
            ->title($page['title'])
            ->description($page['summary'] ?? $page['body'] ?? null)
            ->canonical(url()->current());

        return Inertia::render('StaticPage', $page);
    })->name($slug);
}

Route::post('/cookie-consent', [CookieConsentController::class, 'store'])
    ->name('cookie-consent.store');

Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/artisans', ArtisanDirectoryController::class)->name('public.directory');

Route::get('/p/{slug}', [PublicProfileController::class, 'show'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('public.profile');
Route::get('/p/{slug}/{job}', [PublicJobController::class, 'show'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->where('job', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('public.job');

Route::get('/r/{token}', [PublicReviewController::class, 'show'])
    ->where('token', '[A-Za-z0-9]+')
    ->name('reviews.show');
Route::post('/r/{token}', [PublicReviewController::class, 'store'])
    ->where('token', '[A-Za-z0-9]+')
    ->middleware('throttle:12,1')
    ->name('reviews.store');
Route::get('/r/{token}/thanks', [PublicReviewController::class, 'thanks'])
    ->where('token', '[A-Za-z0-9]+')
    ->name('reviews.thanks');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::post('/impersonation/leave', [UserAdminController::class, 'leaveImpersonation'])
        ->name('impersonation.leave');

    Route::get('/my-page', [AppPlaceholderController::class, 'myPage'])->name('page.index');

    Route::get('/work-log', [WorkLogController::class, 'index'])->name('work-log.index');
    Route::get('/work-log/export', [WorkLogController::class, 'export'])->name('work-log.export');
    Route::get('/work-log/create', [WorkLogController::class, 'create'])->name('work-log.create');
    Route::post('/work-log', [WorkLogController::class, 'store'])->name('work-log.store');
    Route::get('/work-log/{workLog}', [WorkLogController::class, 'show'])->name('work-log.show');
    Route::get('/work-log/{workLog}/edit', [WorkLogController::class, 'edit'])->name('work-log.edit');
    Route::post('/work-log/{workLog}', [WorkLogController::class, 'update'])->name('work-log.update');
    Route::post('/work-log/{workLog}/request-review', [WorkLogController::class, 'requestReview'])
        ->name('work-log.request-review');
    Route::post('/work-log/{workLog}/remind-review', [WorkLogController::class, 'remindReview'])
        ->name('work-log.remind-review');

    Route::get('/tokens', [TokenController::class, 'index'])->name('tokens.index');
    Route::get('/tokens/buy', [TokenController::class, 'buy'])->name('tokens.buy');
    Route::post('/tokens/purchase', [TokenController::class, 'purchase'])->name('tokens.purchase');
    Route::redirect('/credits', '/tokens')->name('credits.index');

    Route::get('/referrals', [ReferralController::class, 'index'])->name('referrals.index');
    Route::post('/notifications/{delivery}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');

    Route::get('/help', [HelpController::class, 'index'])->name('help.index');
    Route::get('/help/chat', [HelpController::class, 'chat'])->name('help.chat');
    Route::post('/help/chat', [HelpController::class, 'send'])->name('help.chat.send');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
    Route::patch('/profile/slug', [ProfileController::class, 'updateSlug'])->name('profile.slug');
    Route::patch('/profile/review-messages', [ProfileController::class, 'updateReviewMessages'])
        ->name('profile.review-messages');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'internal.docs'])
    ->prefix('internal')
    ->name('internal.')
    ->group(function () {
        Route::get('/pricing', PricingDocsController::class)->name('pricing');
    });

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
