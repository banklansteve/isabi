<?php

use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\AppPlaceholderController;
use App\Http\Controllers\ArtisanDirectoryController;
use App\Http\Controllers\CookieConsentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\Internal\PricingDocsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicJobController;
use App\Http\Controllers\EmbedController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\PublicProfileQuoteController;
use App\Http\Controllers\PublicQuoteController;
use App\Http\Controllers\PublicQuoteRequestController;
use App\Http\Controllers\QuoteBuilderController;
use App\Http\Controllers\QuotePipelineController;
use App\Http\Controllers\PublicReviewController;
use App\Http\Controllers\RealtimeController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\WorkLogController;
use App\Support\LegalContent;
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
        'featuredFaqs' => \App\Support\FaqContent::featuredForHome(5),
    ]);
})->name('home');

Route::get('/faq', function () {
    $copy = config('seo.pages.faq');
    $schemaFaqs = \App\Support\FaqContent::schemaEntries();
    if ($schemaFaqs === []) {
        $schemaFaqs = config('seo.faq');
    }

    app(Seo::class)
        ->title($copy['title'])
        ->description($copy['description'])
        ->canonical(route('faq'))
        ->schema(SeoSchema::faq($schemaFaqs));

    return Inertia::render('Faq', [
        'groups' => \App\Support\FaqContent::publicGroups(),
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

Route::get('/about', function () {
    app(Seo::class)
        ->title('About Kraftrack')
        ->description('Why we built a proof-of-work platform for skilled trades across Nigeria — real jobs, client-written reviews, no self-written testimonials.')
        ->canonical(url('/about'));

    return Inertia::render('About', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('about');

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:8,1')
    ->name('contact.store');

Route::get('/careers', function () {
    app(Seo::class)
        ->title('Careers at Kraftrack')
        ->description('Help build trust infrastructure for skilled trades across Nigeria. Remote-friendly, honesty-first product work.')
        ->canonical(url('/careers'));

    return Inertia::render('Careers', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'vacancies' => \App\Models\CareerVacancy::query()
            ->published()
            ->get()
            ->map(fn (\App\Models\CareerVacancy $vacancy) => $vacancy->toPublicArray())
            ->values(),
    ]);
})->name('careers');

foreach (LegalContent::all() as $slug => $page) {
    Route::get('/'.$slug, function () use ($slug, $page) {
        app(Seo::class)
            ->title($page['title'])
            ->description($page['summary'] ?? null)
            ->canonical(url('/'.$slug));

        return Inertia::render('LegalDocument', [
            ...$page,
            'slug' => $slug,
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
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
    ->where('job', '[a-zA-Z0-9]+(?:-[a-zA-Z0-9]+)*')
    ->name('public.job');
Route::post('/p/{slug}/quote', [PublicProfileQuoteController::class, 'store'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->middleware('throttle:8,1')
    ->name('public.profile.quote');
Route::post('/p/{slug}/{job}/quote', [PublicQuoteRequestController::class, 'store'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->where('job', '[a-zA-Z0-9]+(?:-[a-zA-Z0-9]+)*')
    ->middleware('throttle:8,1')
    ->name('public.job.quote');

Route::get('/embed/{slug}', [EmbedController::class, 'profile'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('embed.profile');
Route::get('/embed/{slug}/{job}', [EmbedController::class, 'job'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->where('job', '[a-zA-Z0-9]+(?:-[a-zA-Z0-9]+)*')
    ->name('embed.job');

Route::get('/q/{token}', [PublicQuoteController::class, 'show'])
    ->where('token', '[A-Za-z0-9\-]+')
    ->name('quotes.public.show');
Route::post('/q/{token}', [PublicQuoteController::class, 'respond'])
    ->where('token', '[A-Za-z0-9\-]+')
    ->middleware('throttle:12,1')
    ->name('quotes.public.respond');
Route::get('/q/{token}/thanks', [PublicQuoteController::class, 'thanks'])
    ->where('token', '[A-Za-z0-9\-]+')
    ->name('quotes.public.thanks');
Route::get('/q/{token}/pdf', [PublicQuoteController::class, 'pdf'])
    ->where('token', '[A-Za-z0-9\-]+')
    ->name('quotes.public.pdf');

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

    Route::get('/quotes', [QuotePipelineController::class, 'index'])->name('quotes.index');
    Route::redirect('/quotes/requests', '/quotes');
    Route::redirect('/quotes/sent', '/quotes');
    Route::get('/quotes/requests/{quoteRequest}', [QuoteBuilderController::class, 'show'])->name('quotes.requests.show');
    Route::put('/quotes/requests/{quoteRequest}', [QuoteBuilderController::class, 'update'])->name('quotes.requests.update');
    Route::post('/quotes/requests/{quoteRequest}/send', [QuoteBuilderController::class, 'send'])->name('quotes.requests.send');
    Route::get('/quotes/{quoteRequest}', [QuoteBuilderController::class, 'show'])->name('quotes.show');
    Route::put('/quotes/{quoteRequest}', [QuoteBuilderController::class, 'update'])->name('quotes.update');
    Route::post('/quotes/{quoteRequest}/send', [QuoteBuilderController::class, 'send'])->name('quotes.send');
    Route::post('/quotes/{quoteRequest}/revise', [QuoteBuilderController::class, 'revise'])->name('quotes.revise');
    Route::get('/quotes/{quoteRequest}/pdf', [QuoteBuilderController::class, 'pdf'])->name('quotes.pdf');

    Route::post('/realtime/ping', [RealtimeController::class, 'ping'])
        ->middleware('throttle:60,1')
        ->name('realtime.ping');

    Route::get('/help', [HelpController::class, 'index'])->name('help.index');
    Route::get('/help/chat', [HelpController::class, 'chat'])->name('help.chat');
    Route::get('/help/chat/sync', [HelpController::class, 'sync'])->middleware('throttle:60,1')->name('help.chat.sync');
    Route::post('/help/chat', [HelpController::class, 'send'])->middleware('throttle:support-chat')->name('help.chat.send');
    Route::post('/help/chat/typing', [HelpController::class, 'typing'])->middleware('throttle:60,1')->name('help.chat.typing');
    Route::post('/help/chat/messages/{message}/react', [HelpController::class, 'react'])->middleware('throttle:60,1')->name('help.chat.react');
    Route::post('/help/chat/csat', [HelpController::class, 'csat'])->middleware('throttle:10,1')->name('help.chat.csat');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
    Route::post('/profile/logo', [ProfileController::class, 'updateLogo'])->name('profile.logo');
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
