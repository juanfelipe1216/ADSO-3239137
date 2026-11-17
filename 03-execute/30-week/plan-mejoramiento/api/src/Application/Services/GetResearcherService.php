<?php

declare(strict_types=1);

namespace Lab\Application\Services;

use Lab\Application\DTOs\ResearcherDetails;
use Lab\Application\Ports\ResearcherReader;
use Lab\Domain\DomainViolation;

final readonly class GetResearcherService
{
    public function __construct(private ResearcherReader $researchers) {}

    public function execute(string $id): ResearcherDetails
    {
        return $this->researchers->findById($id)
            ?? throw new DomainViolation('NOT_FOUND', 'El investigador no existe.');
    }
}
