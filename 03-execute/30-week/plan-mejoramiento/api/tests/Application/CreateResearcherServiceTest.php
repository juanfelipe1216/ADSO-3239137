<?php

declare(strict_types=1);

namespace Tests\Application;

use Lab\Application\Ports\IdGenerator;
use Lab\Application\Ports\ResearcherRepository;
use Lab\Application\Services\CreateResearcherService;
use Lab\Domain\DomainViolation;
use Lab\Domain\Researcher;
use Lab\Domain\ValueObjects\DocumentNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryResearcherRepository;

final class CreateResearcherServiceTest extends TestCase
{
    private InMemoryResearcherRepository $researchers;
    private IdGenerator $ids;
    private CreateResearcherService $service;

    protected function setUp(): void
    {
        $this->researchers = new InMemoryResearcherRepository();
        $this->ids = new class implements IdGenerator {
            public int $calls = 0;
            public function generate(): string
            {
                $this->calls++;
                return 'bbbbbbbb-bbbb-4bbb-8bbb-' . str_pad((string) $this->calls, 12, '0', STR_PAD_LEFT);
            }
        };
        $this->service = new CreateResearcherService($this->researchers, $this->ids);
    }

    public function test_creation_trims_fields_and_preserves_document_leading_zeroes(): void
    {
        $researcher = $this->service->execute('  Ana María  ', '  001234  ');
        self::assertSame('bbbbbbbb-bbbb-4bbb-8bbb-000000000001', $researcher->id);
        self::assertSame('Ana María', $researcher->fullName);
        self::assertSame('001234', $researcher->documentNumber->value);
        self::assertSame(['001234'], $this->researchers->lookups);
        self::assertSame($researcher, $this->researchers->findByDocumentNumber('001234'));
        self::assertCount(1, $this->researchers->researchers);
    }

    public function test_documents_with_and_without_leading_zeroes_are_distinct(): void
    {
        $first = $this->service->execute('Ana', '001');
        $second = $this->service->execute('Luis', '1');
        self::assertNotSame($first->id, $second->id);
        self::assertSame('001', $first->documentNumber->value);
        self::assertSame('1', $second->documentNumber->value);
        self::assertCount(2, $this->researchers->researchers);
    }

    public function test_duplicate_trimmed_document_keeps_the_original_researcher(): void
    {
        $first = $this->service->execute('Ana', '001');
        $this->assertViolation('DOCUMENT_TAKEN', fn () => $this->service->execute('Otra persona', ' 001 '));
        self::assertSame($first, $this->researchers->findByDocumentNumber('001'));
        self::assertCount(1, $this->researchers->researchers);
        self::assertSame(1, $this->ids->calls);
    }

    #[DataProvider('invalidFields')]
    public function test_invalid_fields_do_not_query_or_write(string $name, string $document): void
    {
        $this->assertViolation('VALIDATION_ERROR', fn () => $this->service->execute($name, $document));
        self::assertSame([], $this->researchers->lookups);
        self::assertSame([], $this->researchers->researchers);
        self::assertSame(0, $this->ids->calls);
    }

    public static function invalidFields(): array
    {
        return [[' ', '001'], [str_repeat('á', 201), '001'], ['Ana', ' '], ['Ana', str_repeat('1', 31)]];
    }

    public function test_valid_maximum_lengths_are_not_truncated(): void
    {
        $researcher = $this->service->execute(str_repeat('é', 200), str_repeat('0', 30));
        self::assertSame(str_repeat('é', 200), $researcher->fullName);
        self::assertSame(str_repeat('0', 30), $researcher->documentNumber->value);
    }

    public function test_insert_conflict_after_lookup_is_not_converted_to_success(): void
    {
        $repository = new class implements ResearcherRepository {
            public function findByDocumentNumber(string $documentNumber): ?Researcher { return null; }
            public function add(Researcher $researcher): void
            {
                throw new DomainViolation('DOCUMENT_TAKEN', 'El documento ya está registrado.');
            }
        };
        $service = new CreateResearcherService($repository, $this->ids);
        $this->assertViolation('DOCUMENT_TAKEN', fn () => $service->execute('Ana', '001'));
    }

    public function test_fake_repository_rejects_duplicate_insert_without_overwriting(): void
    {
        $first = new Researcher('first-id', 'Ana', new DocumentNumber('001'));
        $this->researchers->add($first);
        $this->assertViolation('DOCUMENT_TAKEN',
            fn () => $this->researchers->add(new Researcher('second-id', 'Luis', new DocumentNumber(' 001 '))));
        self::assertSame($first, $this->researchers->findByDocumentNumber('001'));
        self::assertCount(1, $this->researchers->researchers);
    }

    private function assertViolation(string $code, callable $operation): void
    {
        try {
            $operation();
            self::fail('Se aceptó una operación que debía rechazarse.');
        } catch (DomainViolation $error) {
            self::assertSame($code, $error->errorCode);
        }
    }
}
