<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Lab\Adapters\Security\ArgonPasswordHasher;
use Lab\Adapters\Security\JwtTokens;
use Lab\Adapters\Time\SystemClock;
use Lab\Application\Ports\Clock;
use Lab\Application\Ports\PasswordHasher;
use Lab\Application\Ports\TokenIssuer;
use Lab\Application\Ports\TokenVerifier;

final class LabServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Clock::class, SystemClock::class);
        $this->app->singleton(PasswordHasher::class, ArgonPasswordHasher::class);
        $this->app->singleton(JwtTokens::class, fn ($app) => new JwtTokens((string) config('lab.jwt_secret'), $app->make(Clock::class)));
        $this->app->alias(JwtTokens::class, TokenIssuer::class);
        $this->app->alias(JwtTokens::class, TokenVerifier::class);
    }
}
