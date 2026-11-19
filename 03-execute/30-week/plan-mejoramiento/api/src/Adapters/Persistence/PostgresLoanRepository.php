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

final readonly class PostgresLoanRepository implements LoanRepository, OverdueReader, \Lab\Application\Ports\LoanReader
{
    public function __construct(private ConnectionInterface $db) {}

    public function search(\Lab\Application\DTOs\LoanFilter $filter, \Lab\Application\DTOs\Pagination $pagination, DateTimeImmutable $now): \Lab\Application\DTOs\Page
    {
        $rows = $this->db->select("WITH states AS (
            SELECT l.*, CASE
                WHEN EXISTS (SELECT 1 FROM lab_equipment.loan_item i WHERE i.loan_id = l.id AND i.returned_at IS NULL AND i.due_at < ?) THEN 'overdue'
                WHEN EXISTS (SELECT 1 FROM lab_equipment.loan_item i WHERE i.loan_id = l.id AND i.returned_at IS NULL) THEN 'open'
                ELSE 'closed' END AS status
            FROM lab_equipment.loan l
            WHERE (CAST(? AS timestamptz) IS NULL OR l.loaned_at >= CAST(? AS timestamptz))
              AND (CAST(? AS timestamptz) IS NULL OR l.loaned_at < CAST(? AS timestamptz))
              AND (CAST(? AS uuid) IS NULL OR l.researcher_id = CAST(? AS uuid))
            ), matches AS MATERIALIZED (
                SELECT * FROM states WHERE CAST(? AS text) IS NULL OR status = ?
            ), paged AS (
                SELECT * FROM matches ORDER BY loaned_at DESC, id LIMIT ? OFFSET ?
            )
            SELECT totals.total, p.*,
                (SELECT jsonb_agg(to_jsonb(i) ORDER BY i.id) FROM lab_equipment.loan_item i WHERE i.loan_id = p.id) AS items
            FROM (SELECT count(*) AS total FROM matches) totals LEFT JOIN paged p ON true
            ORDER BY p.loaned_at DESC, p.id",
            [$now->format('Y-m-d H:i:s.uP'),
                $filter->from?->format(DATE_ATOM), $filter->from?->format(DATE_ATOM),
                $filter->until?->format(DATE_ATOM), $filter->until?->format(DATE_ATOM),
                $filter->researcherId, $filter->researcherId, $filter->status, $filter->status,
                $pagination->size, $pagination->offset()]);
        $items = [];
        foreach ($rows as $row) {
            if ($row->id === null) {
                continue;
            }
            $lines = json_decode($row->items, flags: JSON_THROW_ON_ERROR);
            $loan = new Loan($row->id, $row->researcher_id, $row->loaned_by_user_id,
                new DateTimeImmutable($row->loaned_at), array_map(self::item(...), $lines));
            $items[] = (new \Lab\Application\DTOs\LoanData($loan, $now))->jsonSerialize();
        }
        return new \Lab\Application\DTOs\Page($items, (int) $rows[0]->total, $pagination);
    }

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
