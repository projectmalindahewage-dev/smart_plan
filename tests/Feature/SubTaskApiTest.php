<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubTaskApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_subtasks_are_created_with_the_main_task(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/tasks', [
            'title' => 'Main task',
            'date' => '2026-08-22',
            'subtasks' => [
                ['title' => 'First subtask'],
                ['title' => 'Second subtask', 'status' => 'completed'],
            ],
        ])->assertCreated()->assertJsonCount(2, 'task.subtasks');

        $taskId = $response->json('task.id');
        $this->assertDatabaseHas('sub_tasks', ['task_id' => $taskId, 'title' => 'First subtask']);
        $this->assertDatabaseHas('sub_tasks', ['task_id' => $taskId, 'title' => 'Second subtask', 'status' => 'completed', 'completion_percentage' => 100]);
        $this->assertDatabaseHas('task_notifications', ['task_id' => $taskId, 'sub_task_id' => $response->json('task.subtasks.0.id'), 'type' => 'subtask.created']);
        $this->getJson("/api/tasks/{$taskId}")->assertOk()->assertJsonCount(2, 'task.subtasks');
    }

    public function test_a_main_task_without_subtasks_can_be_completed_manually(): void
    {
        $user = User::factory()->create();
        $task = Task::create(['user_id' => $user->id, 'title' => 'Manual task', 'date' => '2026-08-22']);
        Sanctum::actingAs($user);
        $this->patchJson("/api/tasks/{$task->id}/status", ['status' => 'completed'])->assertOk()->assertJsonPath('task.status', 'completed')->assertJsonPath('task.completion_percentage', 100);
    }

    public function test_completing_all_subtasks_automatically_completes_the_main_task(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $task = $this->postJson('/api/tasks', [
            'title' => 'Main task',
            'date' => '2026-08-22',
            'subtasks' => [['title' => 'First'], ['title' => 'Second']],
        ])->assertCreated()->json('task');

        $firstId = $task['subtasks'][0]['id'];
        $secondId = $task['subtasks'][1]['id'];
        $this->patchJson("/api/tasks/{$task['id']}/subtasks/{$firstId}/status", ['status' => 'completed'])
            ->assertOk()->assertJsonPath('task.status', 'pending');
        $this->patchJson("/api/tasks/{$task['id']}/subtasks/{$secondId}/status", ['status' => 'completed'])
            ->assertOk()->assertJsonPath('subtask.status', 'completed')
            ->assertJsonPath('task.status', 'completed')
            ->assertJsonPath('task.completion_percentage', 100);
        $this->assertDatabaseHas('task_notifications', ['task_id' => $task['id'], 'sub_task_id' => $secondId, 'type' => 'subtask.status_updated']);
        $this->assertDatabaseHas('task_notifications', ['task_id' => $task['id'], 'sub_task_id' => null, 'type' => 'task.status_updated']);
    }
}
