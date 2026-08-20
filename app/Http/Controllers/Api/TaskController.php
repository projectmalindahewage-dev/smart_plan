<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tasks = Task::query()
            ->where('user_id', $request->user()->id)
            ->when($request->filled('date'), fn ($query) => $query->whereDate('date', $request->string('date')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderBy('date')
            ->orderBy('start_time')
            ->paginate($request->integer('per_page', 15));

        return response()->json($tasks);
    }

    public function store(Request $request): JsonResponse
    {
        $task = Task::create(array_merge(
            ['user_id' => $request->user()->id],
            $this->validatedData($request),
        ));

        return response()->json(['message' => 'Task created successfully.', 'task' => $task], 201);
    }

    public function show(Request $request, int $task): JsonResponse
    {
        return response()->json(['task' => $this->taskForUser($request, $task)]);
    }

    public function update(Request $request, int $task): JsonResponse
    {
        $task = $this->taskForUser($request, $task);
        $task->update($this->validatedData($request, true));

        return response()->json(['message' => 'Task updated successfully.', 'task' => $task->fresh()]);
    }

    public function destroy(Request $request, int $task): JsonResponse
    {
        $this->taskForUser($request, $task)->delete();

        return response()->json(['message' => 'Task deleted successfully.']);
    }

    private function taskForUser(Request $request, int $id): Task
    {
        return Task::query()->where('user_id', $request->user()->id)->findOrFail($id);
    }

    private function validatedData(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'title' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:255'],
            'priority' => ['sometimes', 'string', Rule::in(['low', 'medium', 'high'])],
            'date' => [$required, 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'status' => ['sometimes', 'string', Rule::in(['pending', 'in_progress', 'completed', 'cancelled'])],
            'enabled' => ['sometimes', 'boolean'],
            'completion_percentage' => ['sometimes', 'integer', 'between:0,100'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}