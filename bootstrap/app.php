<?php

use App\Http\Middleware\ApplySessionLifetime;
use App\Http\Middleware\EnsureInternalDocsAccess;
use App\Http\Middleware\EnsureStaffAssigned;
use App\Http\Middleware\EnsureStaffInvitationVerified;
use App\Http\Middleware\EnsureUserCan;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsStaff;
use App\Http\Middleware\HandleInertiaRequests;
use App\Support\Seo;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            ApplySessionLifetime::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'internal.docs' => EnsureInternalDocsAccess::class,
            'role' => EnsureUserHasRole::class,
            'ability' => EnsureUserCan::class,
            'staff' => EnsureUserIsStaff::class,
            'assigned' => EnsureStaffAssigned::class,
            'staff.invitation' => EnsureStaffInvitationVerified::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            return $request->is('admin', 'admin/*')
                ? route('admin.login')
                : route('login');
        });

        $middleware->redirectUsersTo(function (Request $request) {
            $home = $request->user()?->homeRouteName() ?? 'dashboard';

            return route($home);
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->expectsJson() || $request->is('api/*'),
        );

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return null;
            }

            app(Seo::class)
                ->title('Page not found')
                ->description('That page isn’t on Isabi.')
                ->noindex();

            return Inertia::render('Errors/NotFound', [
                'canLogin' => Route::has('login'),
                'canRegister' => Route::has('register'),
            ])
                ->toResponse($request)
                ->setStatusCode(404);
        });
    })->create();
