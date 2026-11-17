<?php

declare(strict_types=1);

namespace Lab\Application\Services;

use Lab\Application\DTOs\EquipmentData;
use Lab\Application\Ports\CategoryReader;
use Lab\Application\Ports\EquipmentRepository;
use Lab\Domain\DomainViolation;
use Lab\Domain\User;
use Lab\Domain\UserRole;
use Lab\Domain\ValueObjects\Quantity;

final readonly class UpdateEquipmentService
{
    public function __construct(private EquipmentRepository $equipment, private CategoryReader $categories) {}

    public function execute(User $actor, string $id, string $name, string $manufacturer, string $categoryId, int $total): EquipmentData
    {
        if ($actor->role !== UserRole::Admin) {
            throw new DomainViolation('FORBIDDEN_ROLE', 'Solo el administrador puede editar equipos.');
        }
        $stored = $this->equipment->findActive($id)
            ?? throw new DomainViolation('NOT_FOUND', 'El equipo no existe o está dado de baja.');
        $category = $this->categories->findName($categoryId)
            ?? throw new DomainViolation('NOT_FOUND', 'La categoría no existe.');
        $changed = $stored->equipment->revise($name, $manufacturer, $categoryId, $category, new Quantity($total));
        $this->equipment->save($changed, $stored->version);

        return new EquipmentData($changed);
    }
}
