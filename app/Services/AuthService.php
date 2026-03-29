<?php

namespace App\Services;

use App\Models\User;

class AuthService
{
    public function __construct(
        private readonly JwtService $jwtService,
    ) {}

    /**
     * @param  array{name:string,email:string,password:string}  $payload
     * @return array{user:User,accessToken:string,tokenType:string,expiresIn:int}
     */
    public function register(array $payload): array
    {
        $user = User::query()->create($payload);

        return $this->buildAuthPayload($user);
    }

    /**
     * @return array{user:User,accessToken:string,tokenType:string,expiresIn:int}
     */
    public function login(User $user): array
    {
        return $this->buildAuthPayload($user);
    }

    /**
     * @return array{user:User,accessToken:string,tokenType:string,expiresIn:int}
     */
    private function buildAuthPayload(User $user): array
    {
        return [
            'user' => $user,
            'accessToken' => $this->jwtService->generateAccessToken($user),
            'tokenType' => 'Bearer',
            'expiresIn' => $this->jwtService->ttl(),
        ];
    }
}
