<?php
declare(strict_types=1);
namespace Tests\Integration;

use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class CleanBootstrapTest extends TestCase
{
    public function test_empty_database_bootstrap_login_and_restart(): void
    {
        $name = 'bootstrap_test_' . bin2hex(random_bytes(8));
        $password = bin2hex(random_bytes(20));
        $environment = ['DB_DATABASE' => $name, 'ADMIN_USERNAME' => 'initial-admin',
            'ADMIN_PASSWORD' => $password, 'JWT_SECRET' => bin2hex(random_bytes(32))];
        DB::statement('CREATE DATABASE "' . $name . '"');
        $server = null;
        try {
            $run = function (array $args) use ($environment): void {
                $process = new Process([PHP_BINARY, 'artisan', ...$args], base_path(), $environment);
                $process->setTimeout(30);
                $process->run();
                self::assertSame(0, $process->getExitCode(), 'Isolated startup command failed.');
            };
            $run(['migrate', '--force']);
            $run(['lab:bootstrap-admin']);
            config(['database.connections.bootstrap' => array_replace(config('database.connections.pgsql'), ['database' => $name])]);
            $isolated = DB::connection('bootstrap');
            $before = $isolated->table('lab_equipment.user')->first();
            $run(['migrate', '--force']);
            $run(['lab:bootstrap-admin']);
            self::assertSame(1, $isolated->table('lab_equipment.user')->count());
            self::assertEquals($before, $isolated->table('lab_equipment.user')->first());
            self::assertSame(5, $isolated->table('lab_equipment.category')->count());
            $server = new Process([PHP_BINARY, '-S', '127.0.0.1:0', 'public/index.php'], base_path(), $environment);
            $server->start();
            $port = null;
            $server->waitUntil(static function ($type, $output) use (&$port) {
                if (preg_match('/127\\.0\\.0\\.1:(\\d+)\\) started/', $output, $match)) {
                    $port = (int) $match[1];
                }
                return $port !== null;
            });
            self::assertNotNull($port);
            $context = stream_context_create(['http' => ['method' => 'POST', 'timeout' => 8,
                'header' => "Content-Type: application/json\r\n",
                'content' => json_encode(['username' => 'initial-admin', 'password' => $password])]]);
            $response = json_decode(file_get_contents("http://127.0.0.1:$port/api/v1/auth/login", false, $context), true, flags: JSON_THROW_ON_ERROR);
            self::assertSame('admin', $response['role']);
            self::assertSame(28800, $response['expires_in']);
            self::assertNotEmpty($response['access_token']);
        } finally {
            $server?->stop(1);
            DB::purge('bootstrap');
            DB::statement('DROP DATABASE "' . $name . '" WITH (FORCE)');
        }
    }
}
