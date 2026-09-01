<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdministrator
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $administratorEmail = config('admin.email');
        $userEmail = $request->user()?->email;

        if (! is_string($administratorEmail) || blank($administratorEmail)
            || ! is_string($userEmail)
            || ! hash_equals(Str::lower($administratorEmail), Str::lower($userEmail))) {
            abort(403);
        }

        return $next($request);
    }
}
