<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class OpenMeteoService
{
    public function forecast(float $latitude, float $longitude, int $forecastDays = 7, string $timezone = 'auto'): ?array
    {
        try {
            $response = Http::baseUrl(config('services.open_meteo.base_url'))
                ->acceptJson()
                ->timeout(10)
                ->get('/v1/forecast', [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'current' => 'temperature_2m,apparent_temperature,precipitation,rain,weather_code,wind_speed_10m',
                    'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_sum,sunrise,sunset',
                    'timezone' => $timezone,
                    'forecast_days' => $forecastDays,
                ]);

            return $response->successful() ? $response->json() : null;
        } catch (ConnectionException) {
            return null;
        }
    }
}