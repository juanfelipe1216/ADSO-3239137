<?php

declare(strict_types=1);

namespace Tests\Fakes;

use Lab\Application\Ports\ResearcherRepository;
use Lab\Domain\DomainViolation;
use Lab\Domain\Researcher;

final class InMemoryResearcherRepository implements ResearcherRepository
{
    /** @var array<string, Researcher> */
    public array $researchers = [];
    public array $lookups = [];

    public function findByDocumentNumber(string $documentNumber): ?Researcher
    {
        $this->lookups[] = $documentNumber;
        return $this->researchers[$documentNumber] ?? null;
    }

    public function add(Researcher $researcher): void
    {
        $document = $researcher->documentNumber->value;
        if (isset($this->researchers[$document])) {
            throw new DomainViolation('DOCUMENT_TAKEN', 'El documento ya está registrado.');
        }
        $this->researchers[$document] = $researcher;
    }
}
