<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\AppPlaceholderController;
use App\Http\Controllers\CookieConsentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Internal\PricingDocsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\PublicReviewController;
use App\Http\Controllers\WorkLogController;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('home');

Route::get('/faq', function () {
    return Inertia::render('Faq', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('faq');

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
        return Inertia::render('StaticPage', $page);
    })->name($slug);
}

Route::post('/cookie-consent', [CookieConsentController::class, 'store'])
    ->name('cookie-consent.store');

Route::get('/p/{slug}', [PublicProfileController::class, 'show'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('public.profile');

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

    Route::get('/my-page', [AppPlaceholderController::class, 'myPage'])->name('page.index');

    Route::get('/work-log', [WorkLogController::class, 'index'])->name('work-log.index');
    Route::get('/work-log/create', [WorkLogController::class, 'create'])->name('work-log.create');
    Route::post('/work-log', [WorkLogController::class, 'store'])->name('work-log.store');
    Route::get('/work-log/{workLog}', [WorkLogController::class, 'show'])->name('work-log.show');
    Route::get('/work-log/{workLog}/edit', [WorkLogController::class, 'edit'])->name('work-log.edit');
    Route::post('/work-log/{workLog}', [WorkLogController::class, 'update'])->name('work-log.update');
    Route::post('/work-log/{workLog}/request-review', [WorkLogController::class, 'requestReview'])
        ->name('work-log.request-review');

    Route::get('/credits', [AppPlaceholderController::class, 'credits'])->name('credits.index');
    Route::get('/referrals', [AppPlaceholderController::class, 'referrals'])->name('referrals.index');
    Route::get('/help', [AppPlaceholderController::class, 'help'])->name('help.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/slug', [ProfileController::class, 'updateSlug'])->name('profile.slug');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
| Staff / admin area
| - role:super_admin,operations_admin → any staff
| - ability:admin.settings.manage → super admin only (via ability map)
*/
Route::middleware([
    'auth',
    'verified',
    'role:'.UserRole::SuperAdmin->value.','.UserRole::OperationsAdmin->value,
])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::middleware('role:'.UserRole::SuperAdmin->value)->group(function () {
            Route::get('/activity', ActivityLogController::class)->name('activity');
        });
    });

Route::middleware(['auth', 'internal.docs'])
    ->prefix('internal')
    ->name('internal.')
    ->group(function () {
        Route::get('/pricing', PricingDocsController::class)->name('pricing');
    });

require __DIR__.'/auth.php';
