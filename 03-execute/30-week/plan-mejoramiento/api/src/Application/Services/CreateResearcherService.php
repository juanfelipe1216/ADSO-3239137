<?php

declare(strict_types=1);

namespace Lab\Application\Services;

use Lab\Application\Ports\IdGenerator;
use Lab\Application\Ports\ResearcherRepository;
use Lab\Domain\DomainViolation;
use Lab\Domain\Researcher;
use Lab\Domain\ValueObjects\DocumentNumber;
use Lab\Domain\ValueObjects\RequiredText;

final readonly class CreateResearcherService
{
    public function __construct(private ResearcherRepository $researchers, private IdGenerator $ids) {}

    public function execute(string $fullName, string $documentNumber): Researcher
    {
        $fullName = RequiredText::normalize($fullName, 200);
        $document = new DocumentNumber($documentNumber);
        if ($this->researchers->findByDocumentNumber($document->value) !== null) {
            throw new DomainViolation('DOCUMENT_TAKEN', 'El documento ya está registrado.');
        }

        $researcher = new Researcher($this->ids->generate(), $fullName, $document);
        $this->researchers->add($researcher);

        return $researcher;
    }
}
