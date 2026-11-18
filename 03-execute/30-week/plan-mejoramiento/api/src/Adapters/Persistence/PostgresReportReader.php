<?php

declare(strict_types=1);

namespace Lab\Adapters\Persistence;

use Illuminate\Database\ConnectionInterface;
use Lab\Application\DTOs\MostBorrowedRow;
use Lab\Application\Ports\ReportReader;
use Lab\Domain\ValueObjects\DateRange;

final readonly class PostgresReportReader implements ReportReader
{
    public function __construct(private ConnectionInterface $db) {}

    public function mostBorrowed(DateRange $range): array
    {
        $rows = $this->db->select('SELECT i.equipment_id, i.equipment_name, i.category_name, count(*) AS times_borrowed
            FROM lab_equipment.loan l JOIN lab_equipment.loan_item i ON i.loan_id = l.id
            WHERE l.loaned_at >= ? AND l.loaned_at < ?
            GROUP BY i.equipment_id, i.equipment_name, i.category_name
            ORDER BY times_borrowed DESC, i.equipment_name COLLATE "C", i.category_name COLLATE "C", i.equipment_id',
            [$range->from->format('Y-m-d H:i:s.uP'), $range->exclusiveEnd()->format('Y-m-d H:i:s.uP')]);
        return array_map(static fn ($row): MostBorrowedRow => new MostBorrowedRow(
            $row->equipment_id, $row->equipment_name, $row->category_name, (int) $row->times_borrowed), $rows);
    }
}
