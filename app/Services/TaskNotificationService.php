<?php

namespace App\Services;

use App\Models\SubTask;
use App\Models\Task;
use App\Models\TaskNotification;

class TaskNotificationService
{
    public function __construct(private readonly FirebasePushNotificationService $pushNotifications)
    {
    }

    public function taskCreated(Task $task): TaskNotification
    {
        return $this->create($task, 'task.created', 'Task created', "Task '{$task->title}' was created.");
    }

    public function taskUpdated(Task $task): TaskNotification
    {
        return $this->create($task, 'task.updated', 'Task updated', "Task '{$task->title}' was updated.");
    }

    public function taskDeleted(Task $task): TaskNotification
    {
        return $this->create($task, 'task.deleted', 'Task deleted', "Task '{$task->title}' was deleted.");
    }

    public function taskStatusUpdated(Task $task): TaskNotification
    {
        return $this->create($task, 'task.status_updated', 'Task status updated', "Task '{$task->title}' is now {$task->status}.");
    }

    public function subtaskAdded(Task $task, SubTask $subtask): TaskNotification
    {
        return $this->create($task, 'subtask.created', 'Subtask added', "Subtask '{$subtask->title}' was added to '{$task->title}'.", $subtask);
    }

    public function subtaskStatusUpdated(Task $task, SubTask $subtask): TaskNotification
    {
        return $this->create($task, 'subtask.status_updated', 'Subtask status updated', "Subtask '{$subtask->title}' is now {$subtask->status}.", $subtask);
    }

    private function create(Task $task, string $type, string $title, string $message, ?SubTask $subtask = null): TaskNotification
    {
        $notification = TaskNotification::create([
            'user_id' => $task->user_id,
            'task_id' => $task->id,
            'sub_task_id' => $subtask?->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => [
                'task_id' => $task->id,
                'sub_task_id' => $subtask?->id,
                'status' => $subtask?->status ?? $task->status,
            ],
        ]);

        $this->pushNotifications->send($task->user, $notification);

        return $notification;
    }
}
