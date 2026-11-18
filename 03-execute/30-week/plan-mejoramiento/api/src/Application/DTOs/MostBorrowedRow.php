<?php

declare(strict_types=1);

namespace Lab\Application\DTOs;

use JsonSerializable;

final readonly class MostBorrowedRow implements JsonSerializable
{
    public function __construct(public string $equipmentId, public string $equipmentName, public string $categoryName, public int $timesBorrowed) {}

    public function jsonSerialize(): array
    {
        return ['equipment_id' => $this->equipmentId, 'equipment_name' => $this->equipmentName,
            'category_name' => $this->categoryName, 'times_borrowed' => $this->timesBorrowed];
    }
}
