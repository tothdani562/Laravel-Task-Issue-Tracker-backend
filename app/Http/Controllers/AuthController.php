<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function refresh(RefreshRequest $request): JsonResponse
    {
        $authPayload = $this->authService->refresh((string) $request->validated('refreshToken'));

        if ($authPayload === null) {
            return ApiResponse::error('Invalid refresh token.', 401);
        }

        return ApiResponse::success($authPayload);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return ApiResponse::error('Unauthenticated.', 401);
        }

        $this->authService->logout($user);

        return ApiResponse::success([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return ApiResponse::error('Unauthenticated.', 401);
        }

        return ApiResponse::success([
            'user' => $user,
        ]);
    }
}
