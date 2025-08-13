<?php

namespace App\Http\Middleware;

use Closure;

class ApiAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $token = $request->header('Authorization');
        if (!$token) {
            return response()->json(['error' => 'Token missing'], 401);
        }

        $user = \App\SheerappsAccount::where('api_token', $token)->first();
        if (!$user) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        $request->user = $user;
        return $next($request);
    }
}
