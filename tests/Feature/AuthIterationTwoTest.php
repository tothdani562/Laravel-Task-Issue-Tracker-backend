<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthIterationTwoTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_me_refresh_logout_flow_is_stable_and_refresh_is_invalidated_after_logout(): void
    {
        $registerResponse = $this->postJson('/api/auth/register', [
            'name' => 'Iteration Two User',
            'email' => 'iteration-two@example.com',
            'password' => 'Password123',
        ]);

        $registerResponse
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'iteration-two@example.com')
            ->assertJsonPath('data.tokenType', 'Bearer')
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'accessToken',
                    'refreshToken',
                    'tokenType',
                    'expiresIn',
                    'refreshExpiresIn',
                ],
            ]);

        $accessToken = (string) $registerResponse->json('data.accessToken');
        $refreshToken = (string) $registerResponse->json('data.refreshToken');

        $meResponse = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/auth/me');

        $meResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'iteration-two@example.com');

        $refreshResponse = $this->postJson('/api/auth/refresh', [
            'refreshToken' => $refreshToken,
        ]);

        $refreshResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'iteration-two@example.com')
            ->assertJsonPath('data.tokenType', 'Bearer')
            ->assertJsonStructure([
                'success',
                'data' => [
                    'accessToken',
                    'refreshToken',
                    'expiresIn',
                    'refreshExpiresIn',
                ],
            ]);

        $refreshedAccessToken = (string) $refreshResponse->json('data.accessToken');
        $refreshedRefreshToken = (string) $refreshResponse->json('data.refreshToken');

        $this->withHeader('Authorization', 'Bearer '.$refreshedAccessToken)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.message', 'Logged out successfully.');

        $this->postJson('/api/auth/refresh', [
            'refreshToken' => $refreshedRefreshToken,
        ])->assertUnauthorized()->assertJsonPath('message', 'Invalid refresh token.');
    }

    public function test_login_returns_refresh_token_and_enables_me_endpoint(): void
    {
        User::query()->create([
            'name' => 'Login User',
            'email' => 'login-user@example.com',
            'password' => 'Password123',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'login-user@example.com',
            'password' => 'Password123',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'login-user@example.com')
            ->assertJsonPath('data.tokenType', 'Bearer')
            ->assertJsonStructure([
                'success',
                'data' => [
                    'accessToken',
                    'refreshToken',
                    'expiresIn',
                    'refreshExpiresIn',
                ],
            ]);

        $accessToken = (string) $loginResponse->json('data.accessToken');

        $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'login-user@example.com');
    }
}
