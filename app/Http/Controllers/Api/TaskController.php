<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Services\OpenMeteoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tasks = Task::query()->where('user_id', $request->user()->id)
            ->when($request->filled('date'), fn ($query) => $query->whereDate('date', $request->string('date')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->with('subtasks')
            ->orderBy('date')->orderBy('start_time')->paginate($request->integer('per_page', 15));

        return response()->json($tasks);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedData($request);
        $subtasks = $data['subtasks'] ?? [];
        unset($data['subtasks']);

        $task = DB::transaction(function () use ($request, $data, $subtasks) {
            $task = Task::create(array_merge(['user_id' => $request->user()->id], $data));

            foreach ($subtasks as $subtask) {
                if (($subtask['status'] ?? null) === 'completed') {
                    $subtask['completion_percentage'] = 100;
                    $subtask['completed_at'] = now();
                }

                $task->subtasks()->create($subtask);
            }

            if ($subtasks !== [] && ! $task->subtasks()->where('status', '!=', 'completed')->exists()) {
                $task->update(['status' => 'completed', 'completion_percentage' => 100]);
            }

            return $task;
        });

        return response()->json(['message' => 'Task created successfully.', 'task' => $task->fresh('subtasks')], 201);
    }

    public function show(Request $request, int $task, OpenMeteoService $weather): JsonResponse
    {
        $task = $this->taskForUser($request, $task);
        $forecast = $task->weather_data;

        if ($task->latitude !== null && $task->longitude !== null) {
            $freshForecast = $weather->forecast((float) $task->latitude, (float) $task->longitude);

            if ($freshForecast !== null) {
                $task->forceFill(['weather_data' => $freshForecast, 'weather_fetched_at' => now()])->save();
                $forecast = $freshForecast;
            }
        }

        return response()->json([
            'task' => $task->fresh(['subtasks']),
            'weather' => $forecast,
            'weather_suggestions' => $this->weatherSuggestions($forecast, $task->date?->format('Y-m-d')),
        ]);
    }

    public function update(Request $request, int $task): JsonResponse
    {
        $task = $this->taskForUser($request, $task);
        $data = $this->validatedData($request, true);

        $task->update($data);
        return response()->json(['message' => 'Task updated successfully.', 'task' => $task->fresh('subtasks')]);
    }

    public function updateStatus(Request $request, int $task): JsonResponse
    {
        $task = $this->taskForUser($request, $task);
        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(['pending', 'in_progress', 'completed', 'cancelled'])],
        ]);

        if ($data['status'] === 'completed') {
            $data['completion_percentage'] = 100;
        }

        $task->update($data);

        return response()->json(['message' => 'Task status updated successfully.', 'task' => $task->fresh('subtasks')]);
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

    private function weatherSuggestions(?array $forecast, ?string $taskDate): array
    {
        if ($forecast === null) {
            return [];
        }

        $current = $forecast['current'] ?? [];
        $daily = $forecast['daily'] ?? [];
        $dayIndex = $taskDate === null ? false : array_search($taskDate, $daily['time'] ?? [], true);
        $temperature = $dayIndex === false
            ? ($current['temperature_2m'] ?? null)
            : ($daily['temperature_2m_max'][$dayIndex] ?? $current['temperature_2m'] ?? null);
        $precipitation = $dayIndex === false
            ? ($current['precipitation'] ?? $current['rain'] ?? null)
            : ($daily['precipitation_sum'][$dayIndex] ?? $current['precipitation'] ?? $current['rain'] ?? null);
        $windSpeed = $current['wind_speed_10m'] ?? null;
        $weatherCode = $dayIndex === false
            ? ($current['weather_code'] ?? null)
            : ($daily['weather_code'][$dayIndex] ?? $current['weather_code'] ?? null);
        $suggestions = [];

        if (($precipitation !== null && $precipitation > 0) || ($weatherCode !== null && $weatherCode >= 51 && $weatherCode <= 82)) {
            $suggestions[] = 'Rain is expected. Carry an umbrella and allow extra travel time.';
        }

        if ($temperature !== null && $temperature >= 32) {
            $suggestions[] = 'Hot conditions are expected. Schedule outdoor work early, drink water, and use sun protection.';
        } elseif ($temperature !== null && $temperature >= 28) {
            $suggestions[] = 'Warm conditions are expected. Stay hydrated if the task involves being outdoors.';
        } elseif ($temperature !== null && $temperature <= 18) {
            $suggestions[] = 'Cool conditions are expected. Bring an extra layer if the task is outdoors.';
        }

        if ($windSpeed !== null && $windSpeed >= 30) {
            $suggestions[] = 'Strong winds are expected. Secure loose items and consider rescheduling exposed outdoor work.';
        }

        return $suggestions === [] ? ['Conditions look suitable for normal task activities.'] : $suggestions;
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
            'subtasks' => [$partial ? 'prohibited' : 'sometimes', 'array'],
            'subtasks.*.title' => ['required_with:subtasks', 'string', 'max:255'],
            'subtasks.*.description' => ['nullable', 'string'],
            'subtasks.*.status' => ['sometimes', 'string', Rule::in(['pending', 'in_progress', 'completed', 'cancelled'])],
            'subtasks.*.completion_percentage' => ['sometimes', 'integer', 'between:0,100'],
            'subtasks.*.notes' => ['nullable', 'string'],
        ]);
    }
}
