<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class TelegramController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->all();
        $check_hash = $data['hash'] ?? '';

        unset($data['hash']);

        // Sort data by key name
        ksort($data);

        // Create data string
        $check_string = '';
        foreach ($data as $key => $value) {
            $check_string .= "$key=$value\n";
        }
        $check_string = trim($check_string);

        // Create secret key
        $secret_key = hash_hmac('sha256', env('TELEGRAM_BOT_TOKEN'), 'WebAppData', true);
        $hash = hash_hmac('sha256', $check_string, $secret_key);

        if (hash_equals($hash, $check_hash)) {
            // Find or create user
            $user = User::firstOrCreate(
                ['telegram_id' => $data['id']],
                [
                    'name' => $data['first_name'] ?? '',
                    'username' => $data['username'] ?? '',
                    'photo_url' => $data['photo_url'] ?? '',
                ]
            );

            // Generate API token
            $token = bin2hex(random_bytes(32));

            $user->api_token = $token;
            $user->save();

            return response()->json([
                'status' => 'success',
                'token' => $token,
                'user' => $user
            ]);
        }

        return response()->json(['status' => 'error', 'message' => 'Invalid login data'], 403);
    }
}
