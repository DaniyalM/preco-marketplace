<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class KeycloakRefreshController extends Controller
{
    public function refresh(Request $request)
    {
        $refreshToken = $request->cookie('refresh_token');

        if (!$refreshToken) {
            return response()->json(['error' => 'No refresh token'], 401);
        }

        // Exchange Refresh Token with Keycloak for new pair
        $response = Http::asForm()->post(config('keycloak.base_url').'/token', [
            'grant_type' => 'refresh_token',
            'client_id' => config('keycloak.client_id'),
            'client_secret' => config('keycloak.client_secret'),
            'refresh_token' => $refreshToken,
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Invalid refresh token'], 401);
        }

        $data = $response->json();

        return response()->json([
            'access_token' => $data['access_token'],
        ])->withCookie(cookie(
            'refresh_token',
            $data['refresh_token'],
            $data['refresh_expires_in'] / 60,
            '/', null, true, true, false, 'Strict'
        ));
    }
}
