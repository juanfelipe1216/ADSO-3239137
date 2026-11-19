<?php
declare(strict_types=1);
namespace Tests\Integration;

use Lab\Adapters\Time\DemoFileClock;
use Tests\TestCase;

final class DemoFileClockTest extends TestCase
{
    public function test_file_is_read_again_and_system_time_can_be_restored(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'clock');
        try {
            file_put_contents($path, '2040-01-02T03:04:05Z');
            $clock = new DemoFileClock($path, 'testing');
            self::assertSame('2040-01-02T03:04:05+00:00', $clock->now()->format(DATE_ATOM));
            file_put_contents($path, 'system');
            self::assertLessThanOrEqual(1, abs(time() - $clock->now()->getTimestamp()));
            file_put_contents($path, '2040-02-31T03:04:05Z');
            $this->expectException(\RuntimeException::class);
            $clock->now();
        } finally { unlink($path); }
    }

    public function test_missing_file_fails_closed(): void
    {
        $this->expectException(\RuntimeException::class);
        (new DemoFileClock('/missing-' . bin2hex(random_bytes(8)), 'testing'))->now();
    }

    public function test_normal_and_production_environments_reject_demo_configuration(): void
    {
        foreach (['local', 'production'] as $environment) {
            config(['lab.demo_clock_file' => '/tmp/demo-clock']);
            $this->app->instance('env', $environment);
            try {
                (new \App\Providers\LabServiceProvider($this->app))->register();
                self::fail('Demo clock must be rejected outside testing.');
            } catch (\RuntimeException $error) {
                self::assertStringContainsString('aislado', $error->getMessage());
            }
        }
    }
}
