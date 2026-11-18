<?php

declare(strict_types=1);

namespace Lab\Adapters\Persistence;

use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;
use Lab\Application\DTOs\OverdueLine;
use Lab\Application\Ports\LoanRepository;
use Lab\Application\Ports\OverdueReader;
use Lab\Domain\DomainViolation;
use Lab\Domain\Loan;
use Lab\Domain\LoanItem;
use Lab\Domain\ValueObjects\Reference;

final readonly class PostgresLoanRepository implements LoanRepository, OverdueReader
{
    public function __construct(private ConnectionInterface $db) {}

    private static function item(object $row): LoanItem
    {
        return new LoanItem($row->id, $row->equipment_id, $row->equipment_name, new Reference($row->reference),
            $row->category_name, new DateTimeImmutable($row->due_at),
            $row->returned_at === null ? null : new DateTimeImmutable($row->returned_at));
    }

    public function findById(string $id): ?Loan
    {
        $rows = $this->db->select('SELECT i.*, l.researcher_id, l.loaned_by_user_id, l.loaned_at
            FROM lab_equipment.loan l JOIN lab_equipment.loan_item i ON i.loan_id = l.id
            WHERE l.id = ? ORDER BY i.id', [$id]);
        if ($rows === []) {
            return null;
        }
        $first = $rows[0];
        return new Loan($first->loan_id, $first->researcher_id, $first->loaned_by_user_id,
            new DateTimeImmutable($first->loaned_at), array_map(self::item(...), $rows));
    }

    public function pendingItemsForResearcher(string $researcherId): array
    {
        $rows = $this->db->select('SELECT i.* FROM lab_equipment.loan l
            JOIN lab_equipment.loan_item i ON i.loan_id = l.id
            WHERE l.researcher_id = ? AND i.returned_at IS NULL ORDER BY i.id', [$researcherId]);
        return array_map(self::item(...), $rows);
    }

    public function findOverdue(DateTimeImmutable $now): array
    {
        $rows = $this->db->select('SELECT i.*, r.full_name FROM lab_equipment.loan_item i
            JOIN lab_equipment.loan l ON l.id = i.loan_id JOIN lab_equipment.researcher r ON r.id = l.researcher_id
            WHERE i.returned_at IS NULL AND i.due_at < ? ORDER BY i.due_at, i.id', [$now->format('Y-m-d H:i:s.uP')]);
        return array_map(static fn ($row) => new OverdueLine($row->loan_id, $row->full_name, self::item($row)), $rows);
    }

    public function add(Loan $loan): void
    {
        $this->db->table('lab_equipment.loan')->insert(['id' => $loan->id, 'researcher_id' => $loan->researcherId,
            'loaned_by_user_id' => $loan->loanedByUserId, 'loaned_at' => $loan->loanedAt->format('Y-m-d H:i:s.uP')]);
        $rows = [];
        foreach ($loan->items as $item) {
            $rows[] = ['id' => $item->id, 'loan_id' => $loan->id, 'equipment_id' => $item->equipmentId,
                'equipment_name' => $item->equipmentName, 'reference' => $item->reference->value,
                'category_name' => $item->categoryName, 'due_at' => $item->dueAt->format('Y-m-d H:i:s.uP'),
                'returned_at' => $item->returnedAt?->format('Y-m-d H:i:s.uP')];
        }
        $this->db->table('lab_equipment.loan_item')->insert($rows);
    }

    public function markReturned(string $loanId, string $itemId, DateTimeImmutable $at): void
    {
        $line = $this->db->table('lab_equipment.loan_item')->where('loan_id', $loanId)->where('id', $itemId);
        if ((clone $line)->whereNull('returned_at')->update(['returned_at' => $at->format('Y-m-d H:i:s.uP')]) === 1) {
            return;
        }
        if ($line->exists()) {
            throw new DomainViolation('ITEM_ALREADY_RETURNED', 'Esta unidad ya fue devuelta.');
        }
        throw new DomainViolation('NOT_FOUND', 'La línea no pertenece al préstamo.');
    }
}
