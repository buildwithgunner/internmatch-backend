<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Determine the role string based on the user instance or the role attribute
        $userRole = null;
        if ($user instanceof \App\Models\Admin) {
            $userRole = 'admin';
        } elseif ($user instanceof \App\Models\Company) {
            $userRole = 'company';
        } elseif ($user instanceof \App\Models\Recruiter) {
            $userRole = 'recruiter';
        } elseif ($user instanceof \App\Models\User) {
            $userRole = $user->role; // Standard users have a role field (student, etc.)
        }

        if (!$userRole || !in_array($userRole, $roles)) {
            return response()->json([
                'message' => 'Unauthorized. This action requires one of the following roles: ' . implode(', ', $roles)
            ], 403);
        }

        return $next($request);
    }
}
