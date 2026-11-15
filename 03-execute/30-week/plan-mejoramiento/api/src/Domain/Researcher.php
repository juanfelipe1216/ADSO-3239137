<?php

declare(strict_types=1);

namespace Lab\Domain;

use Lab\Domain\ValueObjects\DocumentNumber;
use Lab\Domain\ValueObjects\RequiredText;

final readonly class Researcher
{
    public string $fullName;

    public function __construct(
        public string $id,
        string $fullName,
        public DocumentNumber $documentNumber,
    ) {
        $this->fullName = RequiredText::normalize($fullName, 200);
    }
}
