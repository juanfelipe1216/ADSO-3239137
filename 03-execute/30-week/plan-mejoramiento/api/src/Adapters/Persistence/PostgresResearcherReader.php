<?php

declare(strict_types=1);

namespace Lab\Adapters\Persistence;

use Illuminate\Database\ConnectionInterface;
use Lab\Application\DTOs\Page;
use Lab\Application\DTOs\Pagination;
use Lab\Application\DTOs\ResearcherDetails;
use Lab\Application\Ports\ResearcherReader;
use Lab\Domain\Researcher;
use Lab\Domain\ValueObjects\DocumentNumber;

final readonly class PostgresResearcherReader implements ResearcherReader
{
    public function __construct(private ConnectionInterface $db) {}

    public function search(string $query, Pagination $pagination): Page
    {
        $pattern = PostgresPages::pattern($query);
        return PostgresPages::read($this->db,
            "SELECT id, full_name, document_number FROM lab_equipment.researcher
            WHERE full_name ILIKE ? ESCAPE '!' OR document_number ILIKE ? ESCAPE '!'",
            [$pattern, $pattern], 'full_name', $pagination);
    }

    public function findById(string $id): ?ResearcherDetails
    {
        $row = $this->db->selectOne('SELECT r.id, r.full_name, r.document_number,
            (SELECT count(*) FROM lab_equipment.loan l JOIN lab_equipment.loan_item i ON i.loan_id = l.id
             WHERE l.researcher_id = r.id AND i.returned_at IS NULL) AS active_loans
            FROM lab_equipment.researcher r WHERE r.id = ?', [$id]);
        return $row === null ? null : new ResearcherDetails(
            new Researcher($row->id, $row->full_name, new DocumentNumber($row->document_number)), (int) $row->active_loans);
    }
}
