<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FcmTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fcm_token' => ['required', 'string', 'max:4096'],
        ]);

        $request->user()->update(['fcm_token' => $data['fcm_token']]);

        return response()->json([
            'message' => 'FCM token saved successfully.',
            'fcm_token_registered' => true,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->user()->update(['fcm_token' => null]);

        return response()->json([
            'message' => 'FCM token removed successfully.',
            'fcm_token_registered' => false,
        ]);
    }
}
