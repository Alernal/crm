<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No autorizado'], 401);
            }

            return redirect()->guest(route('login'));
        }

        abort_unless(Auth::user()->is_admin, 403);

        return $next($request);
    }
}
