<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = Auth::user();

        // Check if user is logged in and has the required permission
        // This assumes you have a hasPermission() method in your User Model
        if (!$user || !$user->hasPermission($permission)) {
            abort(403, 'Unauthorized Access - You do not have the ' . $permission . ' permission.');
        }

        return $next($request);
    }
}
