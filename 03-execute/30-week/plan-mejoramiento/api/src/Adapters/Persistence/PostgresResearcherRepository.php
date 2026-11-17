<?php

declare(strict_types=1);

namespace Lab\Adapters\Persistence;

use Illuminate\Database\ConnectionInterface;
use Lab\Application\Ports\ResearcherRepository;
use Lab\Domain\DomainViolation;
use Lab\Domain\Researcher;
use Lab\Domain\ValueObjects\DocumentNumber;

final readonly class PostgresResearcherRepository implements ResearcherRepository
{
    public function __construct(private ConnectionInterface $db) {}

    public function findByDocumentNumber(string $documentNumber): ?Researcher
    {
        $row = $this->db->selectOne('SELECT id, full_name, document_number FROM lab_equipment.researcher WHERE document_number = ?', [$documentNumber]);
        return $row === null ? null : new Researcher($row->id, $row->full_name, new DocumentNumber($row->document_number));
    }

    public function add(Researcher $researcher): void
    {
        $row = $this->db->selectOne('INSERT INTO lab_equipment.researcher (id, full_name, document_number)
            VALUES (?, ?, ?) ON CONFLICT (document_number) DO NOTHING RETURNING id',
            [$researcher->id, $researcher->fullName, $researcher->documentNumber->value]);
        if ($row === null) {
            throw new DomainViolation('DOCUMENT_TAKEN', 'El documento ya está registrado.');
        }
    }
}
