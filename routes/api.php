<?php

use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\SubTaskController;
use App\Http\Controllers\Api\FcmTokenController;
use App\Http\Controllers\Api\TaskNotificationController;
use App\Http\Controllers\Api\WeatherController;
use App\Http\Controllers\ApiAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'application' => config('app.name')]);
});
Route::post('/register', [ApiAuthController::class, 'register'])->middleware('throttle:6,1');
Route::post('/login', [ApiAuthController::class, 'login'])->middleware('throttle:6,1');
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) { return $request->user(); });
    Route::post('/logout', [ApiAuthController::class, 'logout']);
    Route::put('/user/fcm-token', [FcmTokenController::class, 'store']);
    Route::delete('/user/fcm-token', [FcmTokenController::class, 'destroy']);
    Route::apiResource('tasks', TaskController::class);
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus']);
    Route::patch('/tasks/{task}/subtasks/{subTask}/status', [SubTaskController::class, 'updateStatus']);
    Route::get('/notifications', [TaskNotificationController::class, 'index']);
    Route::get('/notifications/{notification}', [TaskNotificationController::class, 'show']);
    Route::get('/weather', [WeatherController::class, 'forecast']);
});
