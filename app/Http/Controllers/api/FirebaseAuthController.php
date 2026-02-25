<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Kreait\Firebase\Factory;

class FirebaseAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        try {
            $auth = (new Factory)
                ->withServiceAccount(config('services.firebase.credentials'))
                ->createAuth();

            $verifiedIdToken = $auth->verifyIdToken($request->id_token);

            $uid = $verifiedIdToken->claims()->get('sub');
            $firebaseUser = $auth->getUser($uid);

            $user = User::updateOrCreate(
                ['firebase_uid' => $uid],
                [
                    'name' => $firebaseUser->displayName ?? 'User',
                    'email' => $firebaseUser->email ?? null,
                    'social_id' => $uid,
                ]
            );

            // ✅ Passport token
            $tokenResult = $user->createToken('mobile');
            $accessToken = $tokenResult->accessToken;

            return response()->json([
                'status' => true,
                'token_type' => 'Bearer',
                'token' => $accessToken,
                'expires_at' => optional($tokenResult->token->expires_at)->toDateTimeString(),
                'user' => $user,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid Firebase Token',
                // In production, you should hide this:
                'error' => $e->getMessage(),
            ], 401);
        }
    }
}
