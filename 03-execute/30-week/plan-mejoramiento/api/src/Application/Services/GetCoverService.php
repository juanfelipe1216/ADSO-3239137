<?php

declare(strict_types=1);

namespace Lab\Application\Services;

use Lab\Application\DTOs\CoverImage;
use Lab\Application\Ports\CoverStorage;
use Lab\Application\Ports\EquipmentRepository;
use Lab\Domain\DomainViolation;

final readonly class GetCoverService
{
    public function __construct(private EquipmentRepository $equipment, private CoverStorage $storage) {}

    public function execute(string $id): CoverImage
    {
        $stored = $this->equipment->findActive($id);
        $key = $stored?->equipment->coverKey;
        if ($key === null || ($image = $this->storage->read($key)) === null) {
            throw new DomainViolation('NOT_FOUND', 'La fotografía no existe.');
        }

        return $image;
    }
}
