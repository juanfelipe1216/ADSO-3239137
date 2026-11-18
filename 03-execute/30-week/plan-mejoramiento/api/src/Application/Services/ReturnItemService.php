<?php

declare(strict_types=1);

namespace Lab\Application\Services;

use Lab\Application\DTOs\LoanItemData;
use Lab\Application\Ports\Clock;
use Lab\Application\Ports\EquipmentRepository;
use Lab\Application\Ports\LoanRepository;
use Lab\Application\Ports\TransactionManager;
use Lab\Domain\DomainViolation;

final readonly class ReturnItemService
{
    public function __construct(
        private LoanRepository $loans,
        private EquipmentRepository $equipment,
        private TransactionManager $transactions,
        private Clock $clock,
    ) {}

    public function execute(string $loanId, string $itemId): LoanItemData
    {
        $loanId = strtolower($loanId);
        $itemId = strtolower($itemId);
        $owner = $this->loans->findById($loanId)
            ?? throw new DomainViolation('NOT_FOUND', 'El préstamo no existe.');

        return $this->transactions->forResearcher($owner->researcherId, function () use ($loanId, $itemId): LoanItemData {
            $loan = $this->loans->findById($loanId)
                ?? throw new DomainViolation('NOT_FOUND', 'El préstamo no existe.');
            foreach ($loan->items as $item) {
                if ($item->id === $itemId) {
                    $returned = $item->returnAt($this->clock->now());
                    $stored = $this->equipment->findActive($item->equipmentId)
                        ?? throw new DomainViolation('NOT_FOUND', 'El equipo no existe o está dado de baja.');
                    $restocked = $stored->equipment->restock();
                    $this->loans->markReturned($loanId, $itemId, $returned->returnedAt);
                    $this->equipment->save($restocked, $stored->version);

                    return new LoanItemData($returned);
                }
            }
            throw new DomainViolation('NOT_FOUND', 'La línea no pertenece al préstamo.');
        });
    }
}
