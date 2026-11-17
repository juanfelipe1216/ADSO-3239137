<?php

declare(strict_types=1);

namespace Lab\Application\DTOs;

use JsonSerializable;
use Lab\Domain\Researcher;

final readonly class ResearcherDetails implements JsonSerializable
{
    public function __construct(public Researcher $researcher, public int $activeLoans) {}

    public function jsonSerialize(): array
    {
        return ['id' => $this->researcher->id, 'full_name' => $this->researcher->fullName,
            'document_number' => $this->researcher->documentNumber->value, 'active_loans' => $this->activeLoans];
    }
}
