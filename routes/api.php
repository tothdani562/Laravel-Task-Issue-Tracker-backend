<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => ApiResponse::success([
    'service' => 'task-manager-api',
    'status' => 'ok',
]));

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::middleware('auth:api')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:api')->prefix('projects')->group(function (): void {
    Route::post('/', [ProjectController::class, 'store']);
    Route::get('/', [ProjectController::class, 'index']);
    Route::get('/{project}', [ProjectController::class, 'show']);
    Route::patch('/{project}', [ProjectController::class, 'update']);
    Route::delete('/{project}', [ProjectController::class, 'destroy']);

    Route::post('/{project}/members', [ProjectController::class, 'addMember']);
    Route::delete('/{project}/members/{memberUserId}', [ProjectController::class, 'removeMember']);

    Route::post('/{projectId}/tasks', [TaskController::class, 'store']);
    Route::get('/{projectId}/tasks', [TaskController::class, 'index']);
    Route::get('/{projectId}/tasks/{taskId}', [TaskController::class, 'show']);
    Route::patch('/{projectId}/tasks/{taskId}', [TaskController::class, 'update']);
    Route::delete('/{projectId}/tasks/{taskId}', [TaskController::class, 'destroy']);
});

Route::middleware('auth:api')->prefix('tasks/{taskId}/comments')->group(function (): void {
    Route::post('/', fn () => ApiResponse::notImplemented('Comment creation is not implemented yet.'));
    Route::get('/', fn () => ApiResponse::notImplemented('Comment listing is not implemented yet.'));
    Route::get('/{commentId}', fn () => ApiResponse::notImplemented('Comment details are not implemented yet.'));
    Route::patch('/{commentId}', fn () => ApiResponse::notImplemented('Comment update is not implemented yet.'));
    Route::delete('/{commentId}', fn () => ApiResponse::notImplemented('Comment deletion is not implemented yet.'));
});
