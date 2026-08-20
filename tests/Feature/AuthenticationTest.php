<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_registration_authenticates_user(): void
    {
        $response = $this->post('/register', ['name' => 'Web User', 'email' => 'web@example.com', 'password' => 'password123', 'password_confirmation' => 'password123']);
        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_api_registration_returns_a_token_and_authenticates_requests(): void
    {
        $response = $this->postJson('/api/register', ['name' => 'API User', 'email' => 'api@example.com', 'password' => 'password123', 'password_confirmation' => 'password123', 'device_name' => 'test-client']);
        $response->assertCreated()->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'name', 'email']]);
        $this->withToken($response->json('token'))->getJson('/api/user')->assertOk()->assertJsonPath('email', 'api@example.com');
    }

    public function test_api_login_rejects_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'user@example.com', 'password' => bcrypt('password123')]);
        $this->postJson('/api/login', ['email' => 'user@example.com', 'password' => 'incorrect'])->assertUnprocessable();
    }
}