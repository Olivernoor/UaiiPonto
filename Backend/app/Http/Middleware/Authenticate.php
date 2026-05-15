<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if (! $request->expectsJson()) {
            return route('login');
        }

        return null;
    }

    /**
     * Get the guards to be checked for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<int, string|null>
     */
    protected function guards(Request $request): array
    {
        // Para API, usar sanctum; para web, usar web
        if ($request->is('api/*') || $request->expectsJson()) {
            return ['sanctum'];
        }
        return ['web'];
    }
}
