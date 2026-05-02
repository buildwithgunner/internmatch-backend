<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Admin;

class IsAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Check if user is authenticated and is an instance of Admin model
        if (!$user || !($user instanceof Admin)) {
            return response()->json([
                'message' => 'Forbidden. This area is restricted to administrators only.'
            ], 403);
        }

        return $next($request);
    }
}
