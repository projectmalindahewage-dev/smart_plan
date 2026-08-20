<?php

use App\Http\Controllers\Api\TaskController;
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
    Route::apiResource('tasks', TaskController::class);
});