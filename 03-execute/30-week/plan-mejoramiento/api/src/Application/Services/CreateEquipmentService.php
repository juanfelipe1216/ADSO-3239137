<?php

declare(strict_types=1);

namespace Lab\Application\Services;

use Lab\Application\DTOs\EquipmentData;
use Lab\Application\Ports\CategoryReader;
use Lab\Application\Ports\EquipmentRepository;
use Lab\Application\Ports\IdGenerator;
use Lab\Domain\DomainViolation;
use Lab\Domain\Equipment;
use Lab\Domain\User;
use Lab\Application\DTOs\AuthenticatedIdentity;
use Lab\Domain\UserRole;
use Lab\Domain\ValueObjects\Quantity;
use Lab\Domain\ValueObjects\Reference;
use Lab\Domain\ValueObjects\RequiredText;

final readonly class CreateEquipmentService
{
    public function __construct(private EquipmentRepository $equipment, private CategoryReader $categories, private IdGenerator $ids) {}

    public function execute(User|AuthenticatedIdentity $actor, string $name, string $manufacturer, string $reference, string $categoryId, int $total): EquipmentData
    {
        if ($actor->role !== UserRole::Admin) {
            throw new DomainViolation('FORBIDDEN_ROLE', 'Solo el administrador puede crear equipos.');
        }
        $name = RequiredText::normalize($name, 250);
        $manufacturer = RequiredText::normalize($manufacturer, 200);
        $reference = new Reference($reference);
        $quantity = new Quantity($total);
        $category = $this->categories->findName($categoryId)
            ?? throw new DomainViolation('NOT_FOUND', 'La categoría no existe.');
        if ($this->equipment->referenceExists($reference->value)) {
            throw new DomainViolation('REFERENCE_TAKEN', 'La referencia ya está registrada.');
        }
        $equipment = Equipment::create($this->ids->generate(), $name, $manufacturer, $reference, $categoryId, $category, $quantity);
        $this->equipment->add($equipment);

        return new EquipmentData($equipment);
    }
}
