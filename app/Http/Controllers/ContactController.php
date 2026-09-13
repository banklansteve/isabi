<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Mail\ContactMessageMail;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Route;

class ContactController extends Controller
{
    public function show(): Response
    {
        app(Seo::class)
            ->title('Contact Kraftrack')
            ->description('Reach the Kraftrack team for account help, billing, partnerships, or press — supporting artisans and clients across Nigeria.')
            ->canonical(url('/contact'));

        return Inertia::render('Contact', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'contactEmail' => 'hello@kraftrack.com',
        ]);
    }

    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        $payload = $request->validated();

        Mail::to('hello@kraftrack.com')->send(new ContactMessageMail($payload));

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Message sent. We usually reply within 1–2 working days (WAT).',
        ]);
    }
}
