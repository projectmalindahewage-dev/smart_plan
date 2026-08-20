<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_manage_their_tasks(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $created = $this->postJson('/api/tasks', [
            'title' => 'Plan sprint',
            'date' => '2026-08-18',
            'priority' => 'high',
            'enabled' => true,
            'completion_percentage' => 25,
        ])->assertCreated()->assertJsonPath('task.title', 'Plan sprint');

        $taskId = $created->json('task.id');
        $this->getJson("/api/tasks/{$taskId}")->assertOk()->assertJsonPath('task.id', $taskId);
        $this->patchJson("/api/tasks/{$taskId}", ['status' => 'in_progress', 'completion_percentage' => 50])->assertOk()->assertJsonPath('task.status', 'in_progress');
        $this->getJson('/api/tasks?date=2026-08-18')->assertOk()->assertJsonCount(1, 'data');
        $this->deleteJson("/api/tasks/{$taskId}")->assertOk();
        $this->assertDatabaseMissing('tasks', ['id' => $taskId]);
    }

    public function test_a_user_cannot_access_another_users_task(): void
    {
        $owner = User::factory()->create();
        $task = Task::create(['user_id' => $owner->id, 'title' => 'Private', 'date' => '2026-08-18']);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/tasks/{$task->id}")->assertNotFound();
    }
}