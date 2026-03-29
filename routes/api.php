<?php

use App\Http\Controllers\AuthController;
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

Route::prefix('projects')->group(function (): void {
    Route::post('/', fn () => ApiResponse::notImplemented('Project creation is not implemented yet.'));
    Route::get('/', fn () => ApiResponse::notImplemented('Project listing is not implemented yet.'));
    Route::get('/{id}', fn () => ApiResponse::notImplemented('Project details are not implemented yet.'));
    Route::patch('/{id}', fn () => ApiResponse::notImplemented('Project update is not implemented yet.'));
    Route::delete('/{id}', fn () => ApiResponse::notImplemented('Project deletion is not implemented yet.'));

    Route::post('/{id}/members', fn () => ApiResponse::notImplemented('Project member add is not implemented yet.'));
    Route::delete('/{id}/members/{memberUserId}', fn () => ApiResponse::notImplemented('Project member removal is not implemented yet.'));

    Route::post('/{projectId}/tasks', fn () => ApiResponse::notImplemented('Task creation is not implemented yet.'));
    Route::get('/{projectId}/tasks', fn () => ApiResponse::notImplemented('Task listing is not implemented yet.'));
    Route::get('/{projectId}/tasks/{taskId}', fn () => ApiResponse::notImplemented('Task details are not implemented yet.'));
    Route::patch('/{projectId}/tasks/{taskId}', fn () => ApiResponse::notImplemented('Task update is not implemented yet.'));
    Route::delete('/{projectId}/tasks/{taskId}', fn () => ApiResponse::notImplemented('Task deletion is not implemented yet.'));
});

Route::prefix('tasks/{taskId}/comments')->group(function (): void {
    Route::post('/', fn () => ApiResponse::notImplemented('Comment creation is not implemented yet.'));
    Route::get('/', fn () => ApiResponse::notImplemented('Comment listing is not implemented yet.'));
    Route::get('/{commentId}', fn () => ApiResponse::notImplemented('Comment details are not implemented yet.'));
    Route::patch('/{commentId}', fn () => ApiResponse::notImplemented('Comment update is not implemented yet.'));
    Route::delete('/{commentId}', fn () => ApiResponse::notImplemented('Comment deletion is not implemented yet.'));
});
