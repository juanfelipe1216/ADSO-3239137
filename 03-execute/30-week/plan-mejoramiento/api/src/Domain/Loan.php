<?php

declare(strict_types=1);

namespace Lab\Domain;

use DateTimeImmutable;
use DateTimeZone;

final readonly class Loan
{
    public DateTimeImmutable $loanedAt;
    /** @var list<LoanItem> */
    public array $items;

    /** @param list<LoanItem> $items */
    public function __construct(
        public string $id,
        public string $researcherId,
        public string $loanedByUserId,
        DateTimeImmutable $loanedAt,
        array $items,
    ) {
        $this->loanedAt = $loanedAt->setTimezone(new DateTimeZone('UTC'));
        if (count($items) < 1 || count($items) > 2) {
            throw new DomainViolation('VALIDATION_ERROR', 'El préstamo debe contener uno o dos equipos.');
        }
        $equipmentIds = [];
        $itemIds = [];
        foreach ($items as $item) {
            if (!$item instanceof LoanItem || $item->dueAt != $this->loanedAt->modify('+5 days')) {
                throw new DomainViolation('VALIDATION_ERROR', 'Cada línea debe vencer cinco días después del préstamo.');
            }
            if (in_array($item->equipmentId, $equipmentIds, true)) {
                throw new DomainViolation('DUPLICATE_EQUIPMENT_IN_LOAN', 'No se puede repetir un equipo en el mismo préstamo.');
            }
            if (in_array($item->id, $itemIds, true)) {
                throw new DomainViolation('VALIDATION_ERROR', 'Las líneas deben tener identificadores diferentes.');
            }
            $equipmentIds[] = $item->equipmentId;
            $itemIds[] = $item->id;
        }
        $this->items = array_values($items);
    }

    public function status(DateTimeImmutable $now): string
    {
        $open = false;
        foreach ($this->items as $item) {
            if ($item->isOverdue($now)) {
                return 'overdue';
            }
            $open = $open || $item->returnedAt === null;
        }

        return $open ? 'open' : 'closed';
    }

    public function returnItem(string $itemId, DateTimeImmutable $at): self
    {
        $items = $this->items;
        foreach ($items as $index => $item) {
            if ($item->id === $itemId) {
                $items[$index] = $item->returnAt($at);

                return new self($this->id, $this->researcherId, $this->loanedByUserId, $this->loanedAt, $items);
            }
        }

        throw new DomainViolation('NOT_FOUND', 'La línea no pertenece al préstamo.');
    }
}
