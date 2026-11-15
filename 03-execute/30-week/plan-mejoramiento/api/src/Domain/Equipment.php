<?php

declare(strict_types=1);

namespace Lab\Domain;

use DateTimeImmutable;
use DateTimeZone;
use Lab\Domain\ValueObjects\Quantity;
use Lab\Domain\ValueObjects\Reference;
use Lab\Domain\ValueObjects\RequiredText;

final readonly class Equipment
{
    public string $name;
    public string $manufacturer;
    public string $categoryName;
    public ?DateTimeImmutable $deletedAt;

    public function __construct(
        public string $id,
        string $name,
        string $manufacturer,
        public Reference $reference,
        public string $categoryId,
        string $categoryName,
        public Quantity $totalUnits,
        public int $availableUnits,
        public ?string $coverKey = null,
        ?DateTimeImmutable $deletedAt = null,
    ) {
        $this->name = RequiredText::normalize($name, 250);
        $this->manufacturer = RequiredText::normalize($manufacturer, 200);
        $this->categoryName = RequiredText::normalize($categoryName, 120);
        if ($availableUnits < 0 || $availableUnits > $totalUnits->value) {
            throw new DomainViolation('VALIDATION_ERROR', 'Las unidades disponibles deben estar entre cero y el total.');
        }
        if ($coverKey !== null && !preg_match('/\A[A-Za-z0-9_-]{1,512}\z/', $coverKey)) {
            throw new DomainViolation('VALIDATION_ERROR', 'La clave de la fotografía debe ser opaca.');
        }
        $this->deletedAt = $deletedAt?->setTimezone(new DateTimeZone('UTC'));
    }

    public static function create(
        string $id, string $name, string $manufacturer, Reference $reference,
        string $categoryId, string $categoryName, Quantity $totalUnits,
    ): self {
        return new self($id, $name, $manufacturer, $reference, $categoryId, $categoryName, $totalUnits, $totalUnits->value);
    }

    public function withdraw(): self
    {
        $this->requireActive();
        if ($this->availableUnits === 0) {
            throw new DomainViolation('EQUIPMENT_NOT_AVAILABLE', 'El equipo no tiene unidades disponibles.');
        }

        return $this->withStock($this->totalUnits, $this->availableUnits - 1);
    }

    public function restock(): self
    {
        $this->requireActive();
        if ($this->availableUnits === $this->totalUnits->value) {
            throw new DomainViolation('VALIDATION_ERROR', 'La devolución superaría el total de unidades.');
        }

        return $this->withStock($this->totalUnits, $this->availableUnits + 1);
    }

    public function changeTotal(Quantity $total): self
    {
        $this->requireActive();

        return $this->withStock($total, $this->availableUnits + $total->value - $this->totalUnits->value);
    }

    public function revise(string $name, string $manufacturer, string $categoryId, string $categoryName, Quantity $total): self
    {
        $stock = $this->changeTotal($total);

        return new self($this->id, $name, $manufacturer, $this->reference, $categoryId, $categoryName,
            $total, $stock->availableUnits, $this->coverKey);
    }

    public function withCover(?string $key): self
    {
        $this->requireActive();

        return new self($this->id, $this->name, $this->manufacturer, $this->reference, $this->categoryId,
            $this->categoryName, $this->totalUnits, $this->availableUnits, $key);
    }

    public function retire(DateTimeImmutable $at): self
    {
        $this->requireActive();
        if ($this->availableUnits !== $this->totalUnits->value) {
            throw new DomainViolation('EQUIPMENT_HAS_OPEN_LOANS', 'El equipo tiene unidades pendientes de devolución.');
        }

        return new self($this->id, $this->name, $this->manufacturer, $this->reference, $this->categoryId,
            $this->categoryName, $this->totalUnits, $this->availableUnits, $this->coverKey, $at);
    }

    private function requireActive(): void
    {
        if ($this->deletedAt !== null) {
            throw new DomainViolation('NOT_FOUND', 'El equipo no existe o está dado de baja.');
        }
    }

    private function withStock(Quantity $total, int $available): self
    {
        return new self($this->id, $this->name, $this->manufacturer, $this->reference, $this->categoryId,
            $this->categoryName, $total, $available, $this->coverKey, $this->deletedAt);
    }
}
