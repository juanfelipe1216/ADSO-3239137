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
use Lab\Application\Ports\CoverStorage;
use Lab\Adapters\Storage\LocalCoverStorage;
use Lab\Adapters\Persistence\PostgresUserRepository;
use Lab\Adapters\Persistence\PostgresResearcherRepository;
use Lab\Application\Ports\UserRepository;
use Lab\Application\Ports\ResearcherRepository;
use Lab\Adapters\Persistence\PostgresResearcherReader;
use Lab\Adapters\Persistence\PostgresEquipmentRepository;
use Lab\Adapters\Persistence\PostgresCategories;
use Lab\Application\Ports\ResearcherReader;
use Lab\Application\Ports\EquipmentRepository;
use Lab\Application\Ports\EquipmentReader;
use Lab\Application\Ports\CategoryReader;

final class LabServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(UserRepository::class, fn ($app) => new PostgresUserRepository($app['db']->connection()));
        $this->app->singleton(ResearcherRepository::class, fn ($app) => new PostgresResearcherRepository($app['db']->connection()));
        $this->app->singleton(ResearcherReader::class, fn ($app) => new PostgresResearcherReader($app['db']->connection()));
        $this->app->singleton(PostgresEquipmentRepository::class, fn ($app) => new PostgresEquipmentRepository($app['db']->connection()));
        $this->app->alias(PostgresEquipmentRepository::class, EquipmentRepository::class);
        $this->app->alias(PostgresEquipmentRepository::class, EquipmentReader::class);
        $this->app->singleton(CategoryReader::class, fn ($app) => new PostgresCategories($app['db']->connection()));
        $this->app->singleton(Clock::class, SystemClock::class);
        $this->app->singleton(PasswordHasher::class, ArgonPasswordHasher::class);
        $this->app->singleton(CoverStorage::class, fn () => new LocalCoverStorage((string) config('lab.covers_dir')));
        $this->app->singleton(JwtTokens::class, fn ($app) => new JwtTokens((string) config('lab.jwt_secret'), $app->make(Clock::class)));
        $this->app->alias(JwtTokens::class, TokenIssuer::class);
        $this->app->alias(JwtTokens::class, TokenVerifier::class);
    }
}
