<?php
declare(strict_types=1);
namespace Tests\Integration;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lab\Application\Ports\UserRepository;
use Lab\Domain\User;
use Tests\TestCase;

final class BootstrapAdminTest extends TestCase
{
    protected function setUp(): void { parent::setUp(); DB::beginTransaction(); }
    protected function tearDown(): void { DB::rollBack(); parent::tearDown(); }

    public function test_bootstrap_is_idempotent_and_admin_can_login(): void
    {
        $name = 'bootstrap-' . Str::uuid();
        $password = bin2hex(random_bytes(16));
        config(['lab.admin_username' => strtoupper($name), 'lab.admin_password' => $password,
            'lab.jwt_secret' => bin2hex(random_bytes(32))]);
        self::assertSame(0, Artisan::call('lab:bootstrap-admin'));
        $before = DB::table('lab_equipment.user')->where('username', $name)->first();
        self::assertSame('argon2id', password_get_info($before->password_hash)['algoName']);
        self::assertSame(0, Artisan::call('lab:bootstrap-admin'));
        self::assertEquals($before, DB::table('lab_equipment.user')->where('username', $name)->first());
        $this->postJson('/api/v1/auth/login', ['username' => $name, 'password' => $password])
            ->assertOk()->assertJsonPath('role', 'admin');
        config(['lab.admin_password' => bin2hex(random_bytes(16))]);
        self::assertSame(1, Artisan::call('lab:bootstrap-admin'));
        self::assertEquals($before, DB::table('lab_equipment.user')->where('username', $name)->first());
        self::assertStringNotContainsString($password, Artisan::output());
    }

    public function test_existing_technician_is_never_promoted(): void
    {
        $name = 'bootstrap-' . Str::uuid();
        app(UserRepository::class)->add(new User((string) Str::uuid(), $name, 'fixture-hash', 'lab_technician'));
        config(['lab.admin_username' => $name, 'lab.admin_password' => bin2hex(random_bytes(16)),
            'lab.jwt_secret' => bin2hex(random_bytes(32))]);
        self::assertSame(1, Artisan::call('lab:bootstrap-admin'));
        self::assertSame('lab_technician', DB::table('lab_equipment.user')->where('username', $name)->value('role'));
    }

    public function test_missing_or_invalid_secrets_fail_without_writing(): void
    {
        $name = 'bootstrap-' . Str::uuid();
        foreach ([['', ''], [bin2hex(random_bytes(32)), 'short'], ['', bin2hex(random_bytes(16))]] as [$jwt, $password]) {
            config(['lab.admin_username' => $name, 'lab.admin_password' => $password, 'lab.jwt_secret' => $jwt]);
            self::assertSame(1, Artisan::call('lab:bootstrap-admin'));
            self::assertSame(0, DB::table('lab_equipment.user')->where('username', $name)->count());
        }
    }
}
