<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaskNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'type' => ['sometimes', 'string', 'max:64'],
        ]);

        $notifications = TaskNotification::query()
            ->where('user_id', $request->user()->id)
            ->when($request->filled('type'), fn ($query) => $query->where('type', $data['type']))
            ->latest()
            ->paginate($data['per_page'] ?? 15);

        return response()->json($notifications);
    }

    public function show(Request $request, int $notification): JsonResponse
    {
        $notification = TaskNotification::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($notification);

        return response()->json(['notification' => $notification]);
    }
}
