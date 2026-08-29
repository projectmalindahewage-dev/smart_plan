<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FcmTokenApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_store_and_remove_an_fcm_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/user/fcm-token', ['fcm_token' => 'device-token'])
            ->assertOk()
            ->assertJsonPath('fcm_token_registered', true);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'fcm_token' => 'device-token']);

        $this->deleteJson('/api/user/fcm-token')
            ->assertOk()
            ->assertJsonPath('fcm_token_registered', false);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'fcm_token' => null]);
    }

    public function test_fcm_token_cannot_be_empty(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/user/fcm-token', ['fcm_token' => ''])->assertUnprocessable();
    }
}
