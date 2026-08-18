<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdultVerified
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->canViewAdultContent()) {
            abort(403, 'You must be 18+ with adult content enabled to access this page.');
        }

        if ($user->requiresAdultSessionLock()) {
            $confirmedAt = (int) $request->session()->get('auth.password_confirmed_at', 0);
            $timeout = (int) config('auth.password_timeout', 10800);

            if ((time() - $confirmedAt) > $timeout) {
                $request->session()->put('url.intended', $request->fullUrl());

                return redirect()->route('password.confirm');
            }
        }

        return $next($request);
    }
}
