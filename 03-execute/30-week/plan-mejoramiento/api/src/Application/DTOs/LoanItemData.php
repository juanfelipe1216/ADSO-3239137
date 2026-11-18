<?php

declare(strict_types=1);

namespace Lab\Application\DTOs;

use JsonSerializable;
use Lab\Domain\LoanItem;

final readonly class LoanItemData implements JsonSerializable
{
    public function __construct(private LoanItem $item) {}

    public function jsonSerialize(): array
    {
        return ['id' => $this->item->id, 'equipment_id' => $this->item->equipmentId,
            'equipment_name' => $this->item->equipmentName, 'reference' => $this->item->reference->value,
            'category_name' => $this->item->categoryName, 'due_at' => $this->item->dueAt->format('Y-m-d\TH:i:s\Z'),
            'returned_at' => $this->item->returnedAt?->format('Y-m-d\TH:i:s\Z')];
    }
}
