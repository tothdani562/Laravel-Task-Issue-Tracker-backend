<?php

namespace App\Services;

use App\Models\User;

class JwtService
{
    public function generateAccessToken(User $user): string
    {
        $issuedAt = time();
        $expiresAt = $issuedAt + $this->ttl();

        $payload = [
            'iss' => $this->issuer(),
            'sub' => (string) $user->getKey(),
            'iat' => $issuedAt,
            'exp' => $expiresAt,
        ];

        return $this->encode($payload);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function decode(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $signature] = $parts;

        $expectedSignature = $this->sign($encodedHeader.'.'.$encodedPayload);

        if (! hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $payloadJson = $this->base64UrlDecode($encodedPayload);

        if ($payloadJson === null) {
            return null;
        }

        $payload = json_decode($payloadJson, true);

        if (! is_array($payload)) {
            return null;
        }

        if (! isset($payload['exp']) || ! is_int($payload['exp']) || $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    public function ttl(): int
    {
        return max(60, (int) config('jwt.ttl', 900));
    }

    private function issuer(): string
    {
        return (string) config('jwt.issuer', 'task-manager-api');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function encode(array $payload): string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256',
        ];

        $encodedHeader = $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR));
        $encodedPayload = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));

        $signature = $this->sign($encodedHeader.'.'.$encodedPayload);

        return $encodedHeader.'.'.$encodedPayload.'.'.$signature;
    }

    private function sign(string $data): string
    {
        $signature = hash_hmac('sha256', $data, $this->secret(), true);

        return $this->base64UrlEncode($signature);
    }

    private function secret(): string
    {
        $secret = (string) config('jwt.secret', '');

        if (str_starts_with($secret, 'base64:')) {
            $decoded = base64_decode(substr($secret, 7), true);

            if ($decoded !== false) {
                return $decoded;
            }
        }

        return $secret;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): ?string
    {
        $padding = strlen($value) % 4;

        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}
