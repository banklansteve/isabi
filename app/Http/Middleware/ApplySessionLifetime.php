<?php

namespace App\Http\Middleware;

use App\Support\Auth\SessionLifetime;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplySessionLifetime
{
    public function __construct(private SessionLifetime $lifetime) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->lifetime->apply($request->user());

        $response = $next($request);

        $this->lifetime->apply($request->user());

        return $response;
    }
}
