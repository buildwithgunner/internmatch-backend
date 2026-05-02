<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
  public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $allowedOrigins = [
            'http://localhost:5173',
            'http://localhost:3000',
            'https://internmatch-frontend-mp9v.vercel.app'
        ];
        
        $origin = $request->headers->get('origin');
        $allowedOrigin = in_array($origin, $allowedOrigins) ? $origin : env('CORS_ALLOWED_ORIGINS', 'https://internmatch-frontend-mp9v.vercel.app');

        $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');

        // Handle preflight OPTIONS request
        if ($request->getMethod() === "OPTIONS") {
            $response->setStatusCode(200);
        }

        return $response;
    }
}