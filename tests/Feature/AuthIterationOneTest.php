<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthIterationOneTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_root_returns_health_check_payload(): void
    {
        $response = $this->getJson('/api');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.service', 'task-manager-api')
            ->assertJsonPath('data.status', 'ok');
    }

    public function test_register_creates_user_and_returns_access_token_payload(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'jane@example.com')
            ->assertJsonPath('data.tokenType', 'Bearer')
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'accessToken',
                    'tokenType',
                    'expiresIn',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
        ]);
    }

    public function test_login_returns_access_token_for_valid_credentials(): void
    {
        User::query()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'john@example.com')
            ->assertJsonPath('data.tokenType', 'Bearer')
            ->assertJsonStructure([
                'success',
                'data' => [
                    'accessToken',
                    'expiresIn',
                ],
            ]);
    }

    public function test_login_fails_for_invalid_credentials(): void
    {
        User::query()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid credentials.');
    }

    public function test_protected_endpoint_is_guarded_by_jwt_auth(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }
}
