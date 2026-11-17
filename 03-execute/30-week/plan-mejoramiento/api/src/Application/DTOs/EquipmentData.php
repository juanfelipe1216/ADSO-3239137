<?php

declare(strict_types=1);

namespace Lab\Application\DTOs;

use JsonSerializable;
use Lab\Domain\Equipment;

final readonly class EquipmentData implements JsonSerializable
{
    public function __construct(private Equipment $equipment) {}

    public function jsonSerialize(): array
    {
        $equipment = $this->equipment;
        return ['id' => $equipment->id, 'name' => $equipment->name, 'manufacturer' => $equipment->manufacturer,
            'reference' => $equipment->reference->value,
            'category' => ['id' => $equipment->categoryId, 'name' => $equipment->categoryName],
            'total_units' => $equipment->totalUnits->value, 'available_units' => $equipment->availableUnits,
            'cover_key' => $equipment->coverKey];
    }
}
