<?php

declare(strict_types=1);

namespace Lab\Application\DTOs;

use DateTimeImmutable;
use Lab\Domain\LoanItem;

final readonly class OverdueLine
{
    public function __construct(public string $loanId, public string $researcherName, public LoanItem $item) {}

    public function toArray(DateTimeImmutable $now): array
    {
        return ['loan_id' => $this->loanId, 'item_id' => $this->item->id,
            'equipment_name' => $this->item->equipmentName, 'researcher_name' => $this->researcherName,
            'due_at' => $this->item->dueAt->format('Y-m-d\TH:i:s\Z'), 'days_late' => $this->item->daysLate($now)];
    }
}
