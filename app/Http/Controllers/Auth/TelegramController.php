<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SheerappsAccount;

class TelegramController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->all();
        $check_hash = $data['hash'] ?? '';
        $referrer = $data['referrer_id'] ?? null;

        unset($data['hash'], $data['referrer_id']);

        // Sort data by keys
        ksort($data);
        $check_string = urldecode(http_build_query($data, '', "\n"));

        // Generate secret key
        $secret_key = hash_hmac('sha256', env('TELEGRAM_BOT_TOKEN'), 'WebAppData', true);
        $hash = hash_hmac('sha256', $check_string, $secret_key);

        if (!hash_equals($hash, $check_hash)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid login data'], 403);
        }

        // Find or create user
        $user = SheerappsAccount::firstOrCreate(
            ['telegram_id' => $data['id']],
            [
                'name' => $data['first_name'] ?? '',
                'username' => $data['username'] ?? '',
                'photo_url' => $data['photo_url'] ?? '',
                'referrer_id' => $referrer
            ]
        );

        // Create API token
        $token = bin2hex(random_bytes(32));
        $user->api_token = $token;
        $user->save();

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'user' => $user
        ]);
    }
}
