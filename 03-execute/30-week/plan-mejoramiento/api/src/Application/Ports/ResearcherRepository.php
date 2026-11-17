<?php

declare(strict_types=1);

namespace Lab\Application\Ports;

use Lab\Domain\DomainViolation;
use Lab\Domain\Researcher;

interface ResearcherRepository
{
    public function findByDocumentNumber(string $documentNumber): ?Researcher;

    /** @throws DomainViolation With DOCUMENT_TAKEN on duplicate normalized documents, including concurrent inserts. */
    public function add(Researcher $researcher): void;
}
