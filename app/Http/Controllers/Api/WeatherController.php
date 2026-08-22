<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OpenMeteoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WeatherController extends Controller
{
    public function forecast(Request $request, OpenMeteoService $weather): JsonResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'forecast_days' => ['sometimes', 'integer', 'between:1,16'],
            'timezone' => ['sometimes', 'string', 'max:64'],
        ]);

        $forecast = $weather->forecast((float) $data['latitude'], (float) $data['longitude'], $data['forecast_days'] ?? 7, $data['timezone'] ?? 'auto');

        if ($forecast === null) {
            return response()->json(['message' => 'Weather service is temporarily unavailable.'], 502);
        }

        return response()->json($forecast);
    }
}