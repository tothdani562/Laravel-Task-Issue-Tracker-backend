<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $authPayload = $this->authService->register($request->validated());

        return ApiResponse::success($authPayload, 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = User::query()->where('email', $request->validated('email'))->first();

        if ($user === null || ! Hash::check((string) $request->validated('password'), $user->getAuthPassword())) {
            return ApiResponse::error('Invalid credentials.', 401);
        }

        return ApiResponse::success($this->authService->login($user));
    }
}
