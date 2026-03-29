<?php

namespace App\Providers;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (filter_var(env('APP_RUNNING_IN_DOCKER', false), FILTER_VALIDATE_BOOL)) {
            Config::set('database.default', 'pgsql');
            Config::set('database.connections.pgsql.host', env('DB_HOST', 'postgres'));
            Config::set('database.connections.pgsql.port', env('DB_PORT', '5432'));
            Config::set('database.connections.pgsql.database', env('DB_DATABASE', 'laravel-eloquent'));
            Config::set('database.connections.pgsql.username', env('DB_USERNAME', 'laravel'));
            Config::set('database.connections.pgsql.password', env('DB_PASSWORD', 'laravel'));
        }

        Auth::viaRequest('jwt', function (Request $request): ?User {
            $token = $request->bearerToken();

            if ($token === null) {
                return null;
            }

            /** @var JwtService $jwtService */
            $jwtService = app(JwtService::class);
            $payload = $jwtService->decode($token);

            if ($payload === null || ! isset($payload['sub']) || ! is_string($payload['sub'])) {
                return null;
            }

            /** @var User|null $user */
            $user = User::query()->find($payload['sub']);

            return $user;
        });
    }
}
