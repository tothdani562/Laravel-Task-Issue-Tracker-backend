<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthIterationSevenTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_endpoint_is_rate_limited_after_configured_attempts(): void
    {
        config(['auth.rate_limits.login_max_attempts' => 3]);

        User::query()->create([
            'name' => 'Rate Limited User',
            'email' => 'rate-limit@example.com',
            'password' => 'Password123',
        ]);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->postJson('/api/auth/login', [
                'email' => 'rate-limit@example.com',
                'password' => 'invalid-password',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/auth/login', [
            'email' => 'rate-limit@example.com',
            'password' => 'invalid-password',
        ])
            ->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonPath('statusCode', 429)
            ->assertJsonPath('message', 'Too many requests.');
    }

    public function test_protected_auth_endpoints_are_rate_limited(): void
    {
        config(['auth.rate_limits.protected_max_attempts' => 2]);

        $loginResponse = $this->postJson('/api/auth/register', [
            'name' => 'Protected Limit User',
            'email' => 'protected-limit@example.com',
            'password' => 'Password123',
        ]);

        $accessToken = (string) $loginResponse->json('data.accessToken');

        $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/auth/me')
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/auth/me')
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/auth/me')
            ->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonPath('statusCode', 429)
            ->assertJsonPath('message', 'Too many requests.');
    }

    public function test_refresh_rejects_access_token(): void
    {
        $registerResponse = $this->postJson('/api/auth/register', [
            'name' => 'Refresh Edge User',
            'email' => 'refresh-edge@example.com',
            'password' => 'Password123',
        ]);

        $accessToken = (string) $registerResponse->json('data.accessToken');

        $this->postJson('/api/auth/refresh', [
            'refreshToken' => $accessToken,
        ])
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid refresh token.');
    }

    public function test_me_rejects_malformed_bearer_token(): void
    {
        $this->withHeader('Authorization', 'Bearer malformed.token.value')
            ->getJson('/api/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_cors_preflight_for_auth_login_allows_configured_origin(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'http://localhost:3000',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'Authorization, Content-Type',
        ])->options('/api/auth/login');

        $response
            ->assertSuccessful()
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:3000');
    }
}
