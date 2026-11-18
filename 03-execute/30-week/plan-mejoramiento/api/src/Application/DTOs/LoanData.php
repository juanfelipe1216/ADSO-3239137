<?php

declare(strict_types=1);

namespace Lab\Application\DTOs;

use DateTimeImmutable;
use JsonSerializable;
use Lab\Domain\Loan;
use Lab\Domain\LoanItem;

final readonly class LoanData implements JsonSerializable
{
    public function __construct(private Loan $loan, private DateTimeImmutable $now) {}

    public function jsonSerialize(): array
    {
        return ['id' => $this->loan->id, 'researcher_id' => $this->loan->researcherId,
            'loaned_at' => $this->loan->loanedAt->format('Y-m-d\TH:i:s\Z'),
            'status' => $this->loan->status($this->now),
            'items' => array_map(static fn (LoanItem $item): array => [
                'id' => $item->id, 'equipment_id' => $item->equipmentId,
                'equipment_name' => $item->equipmentName, 'reference' => $item->reference->value,
                'category_name' => $item->categoryName, 'due_at' => $item->dueAt->format('Y-m-d\TH:i:s\Z'),
                'returned_at' => $item->returnedAt?->format('Y-m-d\TH:i:s\Z'),
            ], $this->loan->items)];
    }
}
