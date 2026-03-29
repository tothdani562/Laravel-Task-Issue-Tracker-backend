<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        private readonly JwtService $jwtService,
    ) {}

    /**
     * @param  array{name:string,email:string,password:string}  $payload
     * @return array{user:User,accessToken:string,refreshToken:string,tokenType:string,expiresIn:int,refreshExpiresIn:int}
     */
    public function register(array $payload): array
    {
        $user = User::query()->create($payload);

        return $this->buildAuthPayload($user);
    }

    /**
     * @return array{user:User,accessToken:string,refreshToken:string,tokenType:string,expiresIn:int,refreshExpiresIn:int}
     */
    public function login(User $user): array
    {
        return $this->buildAuthPayload($user);
    }

    /**
     * @return array{user:User,accessToken:string,refreshToken:string,tokenType:string,expiresIn:int,refreshExpiresIn:int}|null
     */
    public function refresh(string $refreshToken): ?array
    {
        $payload = $this->jwtService->decodeRefreshToken($refreshToken);

        if ($payload === null || ! isset($payload['sub']) || ! is_string($payload['sub'])) {
            return null;
        }

        /** @var User|null $user */
        $user = User::query()->find($payload['sub']);

        if ($user === null || ! is_string($user->refresh_token_hash) || ! Hash::check($refreshToken, $user->refresh_token_hash)) {
            return null;
        }

        return $this->buildAuthPayload($user);
    }

    public function logout(User $user): void
    {
        $user->forceFill([
            'refresh_token_hash' => null,
        ])->save();
    }

    /**
     * @return array{user:User,accessToken:string,refreshToken:string,tokenType:string,expiresIn:int,refreshExpiresIn:int}
     */
    private function buildAuthPayload(User $user): array
    {
        $refreshToken = $this->jwtService->generateRefreshToken($user);

        $user->forceFill([
            'refresh_token_hash' => Hash::make($refreshToken),
        ])->save();

        return [
            'user' => $user,
            'accessToken' => $this->jwtService->generateAccessToken($user),
            'refreshToken' => $refreshToken,
            'tokenType' => 'Bearer',
            'expiresIn' => $this->jwtService->ttl(),
            'refreshExpiresIn' => $this->jwtService->refreshTtl(),
        ];
    }
}
