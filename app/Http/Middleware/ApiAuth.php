<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\SheerappsAccount;
use Illuminate\Support\Facades\Log;

class ApiAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $token = $request->header('Authorization');
            
            if (!$token) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Authorization token required'
                ], 401);
            }

            // Remove 'Bearer ' prefix if present
            $token = str_replace('Bearer ', '', $token);

            // Validate token format (should be 64 characters hex)
            if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
                Log::warning('Invalid token format', [
                    'ip' => $request->ip(),
                    'token_length' => strlen($token)
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid token format'
                ], 401);
            }

            // Find user by token
            $user = SheerappsAccount::where('api_token', $token)->first();
            
            if (!$user) {
                Log::warning('Invalid API token used', [
                    'ip' => $request->ip(),
                    'token' => substr($token, 0, 8) . '...' // Log only first 8 chars for security
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid token'
                ], 401);
            }

            // Check if user is active
            if (!$user->isActive()) {
                Log::warning('Inactive user attempted API access', [
                    'telegram_id' => $user->telegram_id,
                    'status' => $user->status,
                    'ip' => $request->ip()
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Account is not active'
                ], 403);
            }

            // Add user to request for use in controllers
            $request->merge(['auth_user' => $user]);

            // Log successful authentication
            Log::info('API authentication successful', [
                'telegram_id' => $user->telegram_id,
                'endpoint' => $request->path(),
                'ip' => $request->ip()
            ]);

            return $next($request);

        } catch (\Exception $e) {
            Log::error('API authentication error: ' . $e->getMessage(), [
                'ip' => $request->ip(),
                'headers' => $request->headers->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Authentication error'
            ], 500);
        }
    }
}
