<?php

declare(strict_types=1);

namespace Tests\Integration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lab\Application\Ports\UserRepository;
use Lab\Application\Ports\ResearcherRepository;
use Lab\Domain\DomainViolation;
use Lab\Domain\Researcher;
use Lab\Domain\User;
use Lab\Domain\ValueObjects\DocumentNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Fakes\InMemoryUserRepository;
use Tests\Fakes\InMemoryResearcherRepository;
use Tests\TestCase;

final class IdentityRepositoryContractTest extends TestCase
{
    protected function setUp(): void { parent::setUp(); DB::beginTransaction(); }
    protected function tearDown(): void { DB::rollBack(); parent::tearDown(); }
    public static function backends(): array { return [['fake'], ['postgres']]; }

    #[DataProvider('backends')]
    public function test_user_round_trip_duplicate_and_lookup_share_the_same_contract(string $backend): void
    {
        $repository = $backend === 'fake' ? new InMemoryUserRepository() : $this->app->make(UserRepository::class);
        $name = 'operator-' . Str::uuid();
        self::assertNull($repository->findByUsername($name));
        $user = new User((string) Str::uuid(), ' ' . strtoupper($name) . ' ', 'fixture-hash', 'lab_technician');
        $repository->add($user);
        $loaded = $repository->findByUsername($name);
        self::assertSame($user->jsonSerialize(), $loaded->jsonSerialize());
        self::assertSame($user->passwordHash(), $loaded->passwordHash());
        try {
            $repository->add(new User((string) Str::uuid(), $name, 'different-fixture-hash', 'admin'));
            self::fail('Se reemplazó un usuario duplicado.');
        } catch (DomainViolation $error) {
            self::assertSame('USERNAME_TAKEN', $error->errorCode);
        }
        self::assertSame($user->id, $repository->findByUsername($name)->id);
        self::assertNull($repository->findByUsername("' OR 1=1 --"));
    }

    #[DataProvider('backends')]
    public function test_researcher_round_trip_and_document_uniqueness_share_the_same_contract(string $backend): void
    {
        $repository = $backend === 'fake' ? new InMemoryResearcherRepository() : $this->app->make(ResearcherRepository::class);
        $document = '00' . bin2hex(random_bytes(10));
        self::assertNull($repository->findByDocumentNumber($document));
        $researcher = new Researcher((string) Str::uuid(), ' Ana María ', new DocumentNumber(' ' . $document . ' '));
        $repository->add($researcher);
        $loaded = $repository->findByDocumentNumber($document);
        self::assertSame($researcher->id, $loaded->id);
        self::assertSame('Ana María', $loaded->fullName);
        self::assertSame($document, $loaded->documentNumber->value);
        try {
            $repository->add(new Researcher((string) Str::uuid(), 'Otra persona', new DocumentNumber($document)));
            self::fail('Se reemplazó un documento duplicado.');
        } catch (DomainViolation $error) {
            self::assertSame('DOCUMENT_TAKEN', $error->errorCode);
        }
        self::assertSame($researcher->id, $repository->findByDocumentNumber($document)->id);
        self::assertNull($repository->findByDocumentNumber("' OR 1=1 --"));
    }
}
