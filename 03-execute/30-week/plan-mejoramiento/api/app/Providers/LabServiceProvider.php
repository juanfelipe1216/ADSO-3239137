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
use Lab\Application\Ports\ReportReader;
use Lab\Adapters\Persistence\PostgresReportReader;
use Lab\Adapters\Persistence\PostgresLoanRepository;
use Lab\Adapters\Persistence\PostgresTransactionManager;
use Lab\Adapters\Identity\UuidGenerator;
use Lab\Application\Ports\IdGenerator;
use Lab\Application\Ports\LoanRepository;
use Lab\Application\Ports\OverdueReader;
use Lab\Application\Ports\TransactionManager;

final class LabServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $demoFile = (string) config('lab.demo_clock_file');
        if ($demoFile !== '' && !$this->app->environment('testing')) {
            throw new \RuntimeException('El reloj de demostración requiere un entorno de pruebas aislado.');
        }
        $this->app->singleton(IdGenerator::class, UuidGenerator::class);
        $this->app->singleton(TransactionManager::class, fn ($app) => new PostgresTransactionManager($app['db']->connection()));
        $this->app->singleton(PostgresLoanRepository::class, fn ($app) => new PostgresLoanRepository($app['db']->connection()));
        $this->app->alias(PostgresLoanRepository::class, LoanRepository::class);
        $this->app->alias(PostgresLoanRepository::class, \Lab\Application\Ports\LoanReader::class);
        $this->app->alias(PostgresLoanRepository::class, OverdueReader::class);
        $this->app->singleton(UserRepository::class, fn ($app) => new PostgresUserRepository($app['db']->connection()));
        $this->app->singleton(ResearcherRepository::class, fn ($app) => new PostgresResearcherRepository($app['db']->connection()));
        $this->app->singleton(ResearcherReader::class, fn ($app) => new PostgresResearcherReader($app['db']->connection()));
        $this->app->singleton(PostgresEquipmentRepository::class, fn ($app) => new PostgresEquipmentRepository($app['db']->connection()));
        $this->app->alias(PostgresEquipmentRepository::class, EquipmentRepository::class);
        $this->app->alias(PostgresEquipmentRepository::class, EquipmentReader::class);
        $this->app->singleton(CategoryReader::class, fn ($app) => new PostgresCategories($app['db']->connection()));
        $this->app->singleton(ReportReader::class, fn ($app) => new PostgresReportReader($app['db']->connection()));
        if ($demoFile !== '') {
            $this->app->singleton(Clock::class, fn () => new \Lab\Adapters\Time\DemoFileClock($demoFile, 'testing'));
        } else {
            $this->app->singleton(Clock::class, SystemClock::class);
        }
        $this->app->singleton(PasswordHasher::class, ArgonPasswordHasher::class);
        $this->app->singleton(CoverStorage::class, fn () => new LocalCoverStorage((string) config('lab.covers_dir')));
        $this->app->singleton(JwtTokens::class, fn ($app) => new JwtTokens((string) config('lab.jwt_secret'), $app->make(Clock::class)));
        $this->app->alias(JwtTokens::class, TokenIssuer::class);
        $this->app->alias(JwtTokens::class, TokenVerifier::class);
    }
}
