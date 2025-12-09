<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (! $user || (string) $user->role !== '1') {
            // Not admin - redirect to home with error message
            return redirect('/')->with('error', 'Unauthorized: admin access only.');
        }

        return $next($request);
    }
}
