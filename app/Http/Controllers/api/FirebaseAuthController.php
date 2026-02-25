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
            $factory = (new Factory)
                ->withServiceAccount(config('services.firebase.credentials'));

            $auth = $factory->createAuth();

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

            // If using Sanctum
            $token = $user->createToken('mobile')->accessToken;
            return response()->json([
                'status' => true,
                'token' => $token,
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid Firebase Token',
                'error' => $e->getMessage(),
            ], 401);
        }
    }
}
