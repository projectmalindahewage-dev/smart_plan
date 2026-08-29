<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskNotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_paginated_notifications_and_view_a_detail(): void
    {
        $user = User::factory()->create();
        $task = Task::create(['user_id' => $user->id, 'title' => 'Task', 'date' => '2026-08-23']);
        $notifications = collect(range(1, 3))->map(fn (int $number) => TaskNotification::create([
            'user_id' => $user->id,
            'task_id' => $task->id,
            'type' => 'task.updated',
            'title' => "Update {$number}",
            'message' => "Task update {$number}",
            'data' => ['task_id' => $task->id],
        ]));
        Sanctum::actingAs($user);

        $this->getJson('/api/notifications?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('total', 3);
        $this->getJson("/api/notifications/{$notifications->first()->id}")
            ->assertOk()
            ->assertJsonPath('notification.task_id', $task->id)
            ->assertJsonPath('notification.data.task_id', $task->id);
    }

    public function test_user_cannot_view_another_users_notification(): void
    {
        $owner = User::factory()->create();
        $notification = TaskNotification::create([
            'user_id' => $owner->id,
            'type' => 'task.created',
            'title' => 'Task created',
            'message' => 'A task was created.',
        ]);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/notifications/{$notification->id}")->assertNotFound();
    }
}
