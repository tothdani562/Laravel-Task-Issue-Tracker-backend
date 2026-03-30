<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Project;
use App\Policies\ProjectPolicy;
use App\Services\JwtService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
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
        Gate::policy(Project::class, ProjectPolicy::class);

        RateLimiter::for('auth-register', function (Request $request): Limit {
            return Limit::perMinute((int) config('auth.rate_limits.register_max_attempts', 5))
                ->by($request->ip());
        });

        RateLimiter::for('auth-login', function (Request $request): Limit {
            $email = strtolower((string) $request->input('email', ''));

            return Limit::perMinute((int) config('auth.rate_limits.login_max_attempts', 10))
                ->by($request->ip().'|'.$email);
        });

        RateLimiter::for('auth-refresh', function (Request $request): Limit {
            return Limit::perMinute((int) config('auth.rate_limits.refresh_max_attempts', 20))
                ->by($request->ip());
        });

        RateLimiter::for('auth-protected', function (Request $request): Limit {
            $userPart = (string) optional($request->user())->getAuthIdentifier();

            return Limit::perMinute((int) config('auth.rate_limits.protected_max_attempts', 60))
                ->by($request->ip().'|'.$userPart);
        });

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
            $payload = $jwtService->decodeAccessToken($token);

            if ($payload === null || ! isset($payload['sub']) || ! is_string($payload['sub'])) {
                return null;
            }

            /** @var User|null $user */
            $user = User::query()->find($payload['sub']);

            return $user;
        });
    }
}
