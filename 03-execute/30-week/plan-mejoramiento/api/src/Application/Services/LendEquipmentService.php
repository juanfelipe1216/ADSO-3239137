<?php

declare(strict_types=1);

namespace Lab\Application\Services;

use DateTimeZone;
use Lab\Application\DTOs\LoanData;
use Lab\Application\Ports\Clock;
use Lab\Application\Ports\EquipmentRepository;
use Lab\Application\Ports\IdGenerator;
use Lab\Application\Ports\LoanRepository;
use Lab\Application\Ports\ResearcherReader;
use Lab\Application\Ports\TransactionManager;
use Lab\Domain\DomainViolation;
use Lab\Domain\Loan;
use Lab\Domain\LoanItem;
use Lab\Domain\User;

final readonly class LendEquipmentService
{
    public function __construct(
        private EquipmentRepository $equipment,
        private ResearcherReader $researchers,
        private LoanRepository $loans,
        private TransactionManager $transactions,
        private Clock $clock,
        private IdGenerator $ids,
    ) {}

    /** @param list<string> $equipmentIds UUID syntax and authentication are checked at the HTTP boundary. */
    public function execute(User $actor, string $researcherId, array $equipmentIds): LoanData
    {
        if (trim($researcherId) === '' || !array_is_list($equipmentIds) || count($equipmentIds) < 1 || count($equipmentIds) > 2) {
            throw new DomainViolation('VALIDATION_ERROR', 'Selecciona un investigador y uno o dos equipos.');
        }
        foreach ($equipmentIds as $id) {
            if (!is_string($id) || trim($id) === '') {
                throw new DomainViolation('VALIDATION_ERROR', 'Cada línea debe identificar un equipo.');
            }
        }
        $equipmentIds = array_map(strtolower(...), $equipmentIds);
        $researcherId = strtolower($researcherId);
        if (count(array_unique($equipmentIds)) !== count($equipmentIds)) {
            throw new DomainViolation('DUPLICATE_EQUIPMENT_IN_LOAN', 'No se puede repetir un equipo en el mismo préstamo.');
        }

        return $this->transactions->forResearcher($researcherId, function () use ($actor, $researcherId, $equipmentIds): LoanData {
            $now = $this->clock->now()->setTimezone(new DateTimeZone('UTC'));
            if ($this->researchers->findById($researcherId) === null) {
                throw new DomainViolation('NOT_FOUND', 'El investigador no existe.');
            }
            $orderedIds = $equipmentIds;
            sort($orderedIds, SORT_STRING);
            $stored = [];
            foreach ($orderedIds as $id) {
                $stored[$id] = $this->equipment->findActive($id)
                    ?? throw new DomainViolation('NOT_FOUND', 'El equipo no existe o está dado de baja.');
            }
            $pending = $this->loans->pendingItemsForResearcher($researcherId);
            foreach ($pending as $item) {
                if ($item->isOverdue($now)) {
                    throw new DomainViolation('RESEARCHER_HAS_OVERDUE', 'El investigador debe devolver sus equipos vencidos antes de recibir otros.');
                }
            }
            if (count($pending) + count($equipmentIds) > 2) {
                throw new DomainViolation('RESEARCHER_LIMIT_EXCEEDED', 'El préstamo superaría el máximo de dos equipos pendientes.',
                    ['active_loans' => count($pending)]);
            }

            $withdrawn = [];
            foreach ($stored as $id => $row) {
                $withdrawn[$id] = $row->equipment->withdraw();
            }
            $loanId = $this->ids->generate();
            $items = [];
            foreach ($equipmentIds as $id) {
                $items[] = LoanItem::borrow($this->ids->generate(), $stored[$id]->equipment, $now);
            }
            $loan = new Loan($loanId, $researcherId, $actor->id, $now, $items);
            foreach ($withdrawn as $id => $item) {
                $this->equipment->save($item, $stored[$id]->version);
            }
            $this->loans->add($loan);

            return new LoanData($loan, $now);
        });
    }
}
