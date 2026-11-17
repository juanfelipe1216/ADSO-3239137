<?php

declare(strict_types=1);

namespace Tests\Application;

use Lab\Application\Services\GetResearcherService;
use Lab\Domain\DomainViolation;
use Lab\Domain\Researcher;
use Lab\Domain\ValueObjects\DocumentNumber;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryResearcherReader;

final class GetResearcherServiceTest extends TestCase
{
    public function test_detail_counts_pending_lines_not_headers_or_returned_lines(): void
    {
        $reader = new InMemoryResearcherReader([
            new Researcher('id-a', 'Ana', new DocumentNumber('001')),
        ], [
            ['researcher_id' => 'id-a', 'loan_id' => 'loan-1', 'returned_at' => null],
            ['researcher_id' => 'id-a', 'loan_id' => 'loan-1', 'returned_at' => null],
            ['researcher_id' => 'id-a', 'loan_id' => 'loan-2', 'returned_at' => '2026-11-15T00:00:00Z'],
            ['researcher_id' => 'other-id', 'loan_id' => 'loan-3', 'returned_at' => null],
        ]);
        self::assertSame(['id' => 'id-a', 'full_name' => 'Ana', 'document_number' => '001', 'active_loans' => 2],
            (new GetResearcherService($reader))->execute('id-a')->jsonSerialize());
    }

    public function test_researcher_without_loans_has_zero_pending_lines(): void
    {
        $reader = new InMemoryResearcherReader([new Researcher('id-a', 'Ana', new DocumentNumber('001'))]);
        self::assertSame(0, (new GetResearcherService($reader))->execute('id-a')->activeLoans);
    }

    public function test_missing_researcher_is_not_found(): void
    {
        try {
            (new GetResearcherService(new InMemoryResearcherReader()))->execute('missing');
            self::fail('Se encontró un investigador inexistente.');
        } catch (DomainViolation $error) {
            self::assertSame('NOT_FOUND', $error->errorCode);
        }
    }
}
