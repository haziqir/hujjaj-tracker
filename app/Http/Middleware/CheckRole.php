<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if ($user->roles()->whereIn('name', $roles)->exists()) {
            return $next($request);
        }

        if ($user->roles()->where('name', 'pilgrim')->exists()) {
            return redirect()->route('pilgrims.dashboard');
        }

        if ($user->roles()->whereIn('name', ['super-admin', 'group-leader', 'staff'])->exists()) {
            return redirect()->route('dashboard');
        }

        abort(403);
    }
}
