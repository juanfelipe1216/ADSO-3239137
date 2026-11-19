<?php

declare(strict_types=1);

namespace Lab\Application\Services;

use Lab\Application\DTOs\LoanData;
use Lab\Application\Ports\Clock;
use Lab\Application\Ports\LoanRepository;
use Lab\Domain\DomainViolation;

final readonly class GetLoanService
{
    public function __construct(private LoanRepository $loans, private Clock $clock) {}

    public function execute(string $id): LoanData
    {
        $loan = $this->loans->findById($id) ?? throw new DomainViolation('NOT_FOUND', 'El préstamo no existe.');
        return new LoanData($loan, $this->clock->now());
    }
}
