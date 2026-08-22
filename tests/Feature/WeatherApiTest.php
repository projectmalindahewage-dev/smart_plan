<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WeatherApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_get_a_weather_forecast(): void
    {
        Sanctum::actingAs(User::factory()->create());
        Http::fake([
            'https://api.open-meteo.com/*' => Http::response([
                'latitude' => 6.9271,
                'longitude' => 79.8612,
                'current' => ['temperature_2m' => 29.4],
            ]),
        ]);

        $this->getJson('/api/weather?latitude=6.9271&longitude=79.8612&forecast_days=3')
            ->assertOk()
            ->assertJsonPath('current.temperature_2m', 29.4);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.open-meteo.com/v1/forecast?latitude=6.9271&longitude=79.8612&current=temperature_2m%2Capparent_temperature%2Cprecipitation%2Crain%2Cweather_code%2Cwind_speed_10m&daily=weather_code%2Ctemperature_2m_max%2Ctemperature_2m_min%2Cprecipitation_sum%2Csunrise%2Csunset&timezone=auto&forecast_days=3');
    }

    public function test_weather_endpoint_validates_coordinates(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/weather?latitude=100&longitude=79')->assertUnprocessable();
    }
}