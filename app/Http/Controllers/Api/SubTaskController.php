<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubTask;
use App\Models\Task;
use App\Services\TaskNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubTaskController extends Controller
{
    public function index(Request $request, int $task): JsonResponse
    {
        $task = $this->taskForUser($request, $task);
        return response()->json(['subtasks' => $task->subtasks()->oldest()->get()]);
    }

    public function store(Request $request, int $task, TaskNotificationService $notifications): JsonResponse
    {
        $task = $this->taskForUser($request, $task);
        $subtask = $task->subtasks()->create($this->validatedData($request));
        $notifications->subtaskAdded($task, $subtask);
        $this->synchronizeParentStatus($task);

        return response()->json(['message' => 'Subtask created successfully.', 'subtask' => $subtask], 201);
    }

    public function update(Request $request, int $task, int $subTask): JsonResponse
    {
        $task = $this->taskForUser($request, $task);
        $subtask = $this->subtaskForTask($task, $subTask);
        $data = $this->validatedData($request, true);

        if (($data['status'] ?? null) === 'completed') {
            $data['completion_percentage'] = 100;
            $data['completed_at'] = now();
        } elseif (array_key_exists('status', $data)) {
            $data['completed_at'] = null;
        }

        $subtask->update($data);
        $task = $this->synchronizeParentStatus($task);

        return response()->json(['message' => 'Subtask updated successfully.', 'subtask' => $subtask->fresh(), 'task' => $task]);
    }

    public function updateStatus(Request $request, int $task, int $subTask, TaskNotificationService $notifications): JsonResponse
    {
        $task = $this->taskForUser($request, $task);
        $previousTaskStatus = $task->status;
        $subtask = $this->subtaskForTask($task, $subTask);
        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(['pending', 'in_progress', 'completed', 'cancelled'])],
        ]);

        if ($data['status'] === 'completed') {
            $data['completion_percentage'] = 100;
            $data['completed_at'] = now();
        } else {
            $data['completed_at'] = null;
        }

        $subtask->update($data);
        $task = $this->synchronizeParentStatus($task);
        $notifications->subtaskStatusUpdated($task, $subtask);

        if ($task->status !== $previousTaskStatus) {
            $notifications->taskStatusUpdated($task);
        }

        return response()->json([
            'message' => 'Subtask status updated successfully.',
            'subtask' => $subtask->fresh(),
            'task' => $task->load('subtasks'),
        ]);
    }

    public function complete(Request $request, int $task, int $subTask): JsonResponse
    {
        $task = $this->taskForUser($request, $task);
        $subtask = $this->subtaskForTask($task, $subTask);
        $subtask->update(['status' => 'completed', 'completion_percentage' => 100, 'completed_at' => now()]);
        $task = $this->synchronizeParentStatus($task);

        return response()->json(['message' => 'Subtask completed successfully.', 'subtask' => $subtask->fresh(), 'task' => $task]);
    }

    public function destroy(Request $request, int $task, int $subTask): JsonResponse
    {
        $task = $this->taskForUser($request, $task);
        $this->subtaskForTask($task, $subTask)->delete();
        $this->synchronizeParentStatus($task);

        return response()->json(['message' => 'Subtask deleted successfully.']);
    }

    private function taskForUser(Request $request, int $id): Task
    {
        return Task::query()->where('user_id', $request->user()->id)->findOrFail($id);
    }

    private function subtaskForTask(Task $task, int $id): SubTask
    {
        return $task->subtasks()->findOrFail($id);
    }

    private function synchronizeParentStatus(Task $task): Task
    {
        if (! $task->subtasks()->exists()) {
            return $task->fresh();
        }

        $allCompleted = ! $task->subtasks()->where('status', '!=', 'completed')->exists();

        if ($allCompleted) {
            $task->update(['status' => 'completed', 'completion_percentage' => 100]);
        } elseif ($task->status === 'completed') {
            $task->update([
                'status' => 'in_progress',
                'completion_percentage' => (int) round($task->subtasks()->avg('completion_percentage')),
            ]);
        }

        return $task->fresh();
    }

    private function validatedData(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'title' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', Rule::in(['pending', 'in_progress', 'completed', 'cancelled'])],
            'completion_percentage' => ['sometimes', 'integer', 'between:0,100'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
