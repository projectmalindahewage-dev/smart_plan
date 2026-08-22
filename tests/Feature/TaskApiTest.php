<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_manage_their_tasks(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $created = $this->postJson('/api/tasks', ['title' => 'Plan sprint', 'date' => '2026-08-18', 'priority' => 'high', 'enabled' => true, 'completion_percentage' => 25, 'latitude' => 6.9271, 'longitude' => 79.8612])->assertCreated()->assertJsonPath('task.title', 'Plan sprint');
        $taskId = $created->json('task.id');
        Http::fake(['https://api.open-meteo.com/*' => Http::response(['current' => ['temperature_2m' => 29.4]])]);
        $this->getJson("/api/tasks/{$taskId}")->assertOk()->assertJsonPath('task.id', $taskId)->assertJsonPath('weather.current.temperature_2m', 29.4)->assertJsonPath('task.weather_data.current.temperature_2m', 29.4)->assertJsonPath('weather_suggestions.0', 'Warm conditions are expected. Stay hydrated if the task involves being outdoors.');
        Http::assertSent(fn ($request) => str_contains($request->url(), 'latitude=6.9271') && str_contains($request->url(), 'longitude=79.8612'));
        $this->assertNotNull(Task::findOrFail($taskId)->weather_fetched_at);
        $this->patchJson("/api/tasks/{$taskId}", ['status' => 'in_progress', 'completion_percentage' => 50])->assertOk()->assertJsonPath('task.status', 'in_progress');
        $this->deleteJson("/api/tasks/{$taskId}")->assertOk();
    }

    public function test_a_user_cannot_access_another_users_task(): void
    {
        $owner = User::factory()->create();
        $task = Task::create(['user_id' => $owner->id, 'title' => 'Private', 'date' => '2026-08-18']);
        Sanctum::actingAs(User::factory()->create());
        $this->getJson("/api/tasks/{$task->id}")->assertNotFound();
    }
}
