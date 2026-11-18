<?php

declare(strict_types=1);

namespace Lab\Domain;

use DateTimeImmutable;
use DateTimeZone;
use Lab\Domain\ValueObjects\Reference;
use Lab\Domain\ValueObjects\RequiredText;

final readonly class LoanItem
{
    public string $equipmentName;
    public string $categoryName;
    public DateTimeImmutable $dueAt;
    public ?DateTimeImmutable $returnedAt;

    public function __construct(
        public string $id,
        public string $equipmentId,
        string $equipmentName,
        public Reference $reference,
        string $categoryName,
        DateTimeImmutable $dueAt,
        ?DateTimeImmutable $returnedAt = null,
    ) {
        $this->equipmentName = RequiredText::normalize($equipmentName, 250);
        $this->categoryName = RequiredText::normalize($categoryName, 120);
        $this->dueAt = $dueAt->setTimezone(new DateTimeZone('UTC'));
        $this->returnedAt = $returnedAt?->setTimezone(new DateTimeZone('UTC'));
        if ($this->returnedAt !== null && $this->returnedAt < $this->dueAt->modify('-5 days')) {
            throw new DomainViolation('VALIDATION_ERROR', 'La devolución no puede ser anterior al préstamo.');
        }
    }

    public static function borrow(string $id, Equipment $equipment, DateTimeImmutable $at): self
    {
        if ($equipment->deletedAt !== null) {
            throw new DomainViolation('NOT_FOUND', 'El equipo no existe o está dado de baja.');
        }
        $at = $at->setTimezone(new DateTimeZone('UTC'));

        return new self($id, $equipment->id, $equipment->name, $equipment->reference,
            $equipment->categoryName, $at->modify('+5 days'));
    }

    public function isOverdue(DateTimeImmutable $now): bool
    {
        return $this->returnedAt === null && $this->dueAt < $now;
    }

    public function daysLate(DateTimeImmutable $now): int
    {
        if (!$this->isOverdue($now)) {
            return 0;
        }

        return $this->dueAt->diff($now->setTimezone(new DateTimeZone('UTC')))->days;
    }

    public function returnAt(DateTimeImmutable $at): self
    {
        if ($this->returnedAt !== null) {
            throw new DomainViolation('ITEM_ALREADY_RETURNED', 'Esta unidad ya fue devuelta.');
        }

        return new self($this->id, $this->equipmentId, $this->equipmentName, $this->reference,
            $this->categoryName, $this->dueAt, $at);
    }
}
