<?php

declare(strict_types=1);

namespace Tests\Contract;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Lab\Application\Ports\PasswordHasher;
use Lab\Application\Ports\UserRepository;
use Lab\Application\Ports\TokenIssuer;
use Lab\Domain\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Fakes\ImageFixture;
use Tests\TestCase;

final class CatalogHttpTest extends TestCase
{
    private User $admin;
    private string $token;
    private string $password;

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        config(['lab.jwt_secret' => bin2hex(random_bytes(32))]);
        $this->password = bin2hex(random_bytes(16));
        $this->admin = new User((string) Str::uuid(), 'admin-' . Str::uuid(),
            app(PasswordHasher::class)->hash($this->password), 'admin');
        app(UserRepository::class)->add($this->admin);
        $this->token = app(TokenIssuer::class)->issue($this->admin, 28800);
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public static function protectedRoutes(): array
    {
        $id = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        return [['POST', '/users'], ['GET', '/categories'], ['GET', '/equipment'], ['GET', "/equipment/$id"],
            ['POST', '/equipment'], ['PUT', "/equipment/$id"], ['DELETE', "/equipment/$id"],
            ['PUT', "/equipment/$id/cover"], ['GET', "/equipment/$id/cover"], ['DELETE', "/equipment/$id/cover"],
            ['GET', '/researchers'], ['POST', '/researchers'], ['GET', "/researchers/$id"],
            ['POST', '/loans'], ['POST', "/loans/$id/items/$id/return"], ['GET', '/reports/most-borrowed']];
    }

    #[DataProvider('protectedRoutes')]
    public function test_every_protected_route_rejects_missing_token(string $method, string $path): void
    {
        $this->json($method, '/api/v1' . $path)->assertStatus(401)->assertJsonStructure(['code', 'message', 'details'])
            ->assertJsonPath('code', 'UNAUTHENTICATED')->assertJsonPath('details', []);
    }

    public function test_admin_catalog_researcher_login_and_cover_flow(): void
    {
        $this->postJson('/api/v1/auth/login', ['username' => strtoupper($this->admin->username), 'password' => $this->password])
            ->assertOk()->assertJsonPath('role', 'admin')->assertJsonPath('expires_in', 28800);
        $this->postJson('/api/v1/auth/login', ['username' => $this->admin->username, 'password' => 'wrong'])
            ->assertStatus(401)->assertJsonPath('code', 'INVALID_CREDENTIALS');
        $this->withToken($this->token);
        $this->getJson('/api/v1/categories')->assertOk()->assertJsonCount(5, 'items');
        $operator = 'operator-' . Str::uuid();
        $this->postJson('/api/v1/users', ['username' => $operator, 'password' => $this->password, 'role' => 'lab_technician'])
            ->assertCreated()->assertJsonMissingPath('password_hash')->assertJsonMissingPath('password');
        $this->postJson('/api/v1/users', ['username' => $operator, 'password' => $this->password, 'role' => 'lab_technician'])->assertStatus(409);
        $category = '11111111-1111-4111-8111-111111111111';
        $data = ['name' => 'Equipo de prueba', 'manufacturer' => 'Fabricante', 'reference' => (string) Str::uuid(), 'category_id' => $category, 'total_units' => 2];
        $id = $this->postJson('/api/v1/equipment', $data)->assertCreated()->assertJsonPath('available_units', 2)->json('id');
        $this->postJson('/api/v1/equipment', $data)->assertStatus(409);
        $this->getJson("/api/v1/equipment/$id")->assertOk();
        $this->getJson('/api/v1/equipment?q=Equipo&page=999&size=1')->assertOk()->assertJsonCount(0, 'items');
        $edited = $data;
        unset($edited['reference']);
        $edited['total_units'] = 3;
        $this->putJson("/api/v1/equipment/$id", $edited)->assertOk()->assertJsonPath('available_units', 3);
        try {
            $file = UploadedFile::fake()->createWithContent('photo.png', ImageFixture::png());
            $this->call('PUT', "/api/v1/equipment/$id/cover", [], [], ['file' => $file],
                ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->token])->assertOk()->assertJsonMissingPath('path');
            $image = $this->get("/api/v1/equipment/$id/cover")->assertOk()->assertHeader('Content-Type', 'image/png');
            self::assertSame(ImageFixture::png(), $image->getContent());
        } finally {
            $this->deleteJson("/api/v1/equipment/$id/cover")->assertNoContent();
        }
        $this->getJson("/api/v1/equipment/$id/cover")->assertNotFound();
        $doc = bin2hex(random_bytes(12));
        $researcher = $this->postJson('/api/v1/researchers', ['full_name' => 'Investigador', 'document_number' => $doc])
            ->assertCreated()->json('id');
        $this->postJson('/api/v1/researchers', ['full_name' => 'Otro', 'document_number' => $doc])->assertStatus(409);
        $this->getJson("/api/v1/researchers/$researcher")->assertOk()->assertJsonPath('active_loans', 0);
        $this->getJson('/api/v1/researchers?q=' . $doc)->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/api/v1/reports/most-borrowed?from=2045-01-01&to=2045-01-01')->assertOk()->assertJsonPath('rows', []);
        $this->deleteJson("/api/v1/equipment/$id")->assertNoContent();
        $this->getJson("/api/v1/equipment/$id")->assertNotFound();
    }

    public function test_role_and_invalid_inputs_have_uniform_errors(): void
    {
        $tech = new User((string) Str::uuid(), 'tech', 'fixture-hash', 'lab_technician');
        $this->withToken(app(TokenIssuer::class)->issue($tech, 28800));
        foreach ([['POST', '/users'], ['POST', '/equipment'], ['PUT', '/equipment/invalid'],
            ['DELETE', '/equipment/invalid'], ['PUT', '/equipment/invalid/cover'],
            ['DELETE', '/equipment/invalid/cover'], ['GET', '/reports/most-borrowed']] as [$method, $path]) {
            $this->json($method, '/api/v1' . $path)->assertStatus(403)->assertJsonPath('code', 'FORBIDDEN_ROLE');
        }
        $this->withToken($this->token);
        foreach (['/equipment?size=51', '/equipment?page=0', '/equipment?size[]=1', '/equipment?extra=1',
            '/equipment?category_id=invalid', '/equipment/invalid', '/researchers?q[]=x',
            '/reports/most-borrowed?from=2026-11-18'] as $path) {
            $this->getJson('/api/v1' . $path)->assertStatus(422)->assertJsonStructure(['code', 'message', 'details']);
        }
        $this->getJson('/api/v1/reports/most-borrowed?from=2026-11-18&to=2026-11-17')
            ->assertStatus(422)->assertJsonPath('code', 'INVALID_DATE_RANGE');
        $this->postJson('/api/v1/users', ['username' => 'x', 'password' => $this->password, 'role' => 'admin'])->assertStatus(422);
        $this->postJson('/api/v1/researchers', ['full_name' => [], 'document_number' => '1'])->assertStatus(422);
        $this->deleteJson('/api/v1/researchers/' . Str::uuid())->assertStatus(405);
    }
}
