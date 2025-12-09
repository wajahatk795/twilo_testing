<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfNotAuthBack
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check()) {
            $previous = url()->previous();
            // Avoid redirect loop: if previous is same as current or empty, send to login page
            if (empty($previous) || $previous === url()->current()) {
                return redirect('/login')->with('error', 'Please login to access that page.');
            }

            return redirect()->to($previous)->with('error', 'Please login to access that page.');
        }

        return $next($request);
    }
}
