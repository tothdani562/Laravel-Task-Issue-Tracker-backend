<?php

use App\Support\ApiResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ApiResponse::success([
        'service' => 'task-manager-api',
        'status' => 'ok',
    ]);
});
