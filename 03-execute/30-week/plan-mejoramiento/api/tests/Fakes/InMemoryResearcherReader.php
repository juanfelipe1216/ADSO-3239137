<?php

declare(strict_types=1);

namespace Tests\Fakes;

use Lab\Application\DTOs\Page;
use Lab\Application\DTOs\Pagination;
use Lab\Application\DTOs\ResearcherDetails;
use Lab\Application\Ports\ResearcherReader;
use Lab\Domain\Researcher;

final class InMemoryResearcherReader implements ResearcherReader
{
    public int $searchCalls = 0;

    /** @param list<Researcher> $researchers
     *  @param list<array{researcher_id: string, loan_id: string, returned_at: ?string}> $lines
     */
    public function __construct(public array $researchers = [], public array $lines = []) {}

    public function search(string $query, Pagination $pagination): Page
    {
        $matches = array_values(array_filter($this->researchers, static fn (Researcher $researcher): bool =>
            mb_stripos($researcher->fullName, $query, 0, 'UTF-8') !== false
            || mb_stripos($researcher->documentNumber->value, $query, 0, 'UTF-8') !== false));
        usort($matches, static fn (Researcher $left, Researcher $right): int =>
            [$left->fullName, $left->id] <=> [$right->fullName, $right->id]);
        $this->searchCalls++;
        $items = array_map(static fn (Researcher $researcher): array => [
            'id' => $researcher->id, 'full_name' => $researcher->fullName,
            'document_number' => $researcher->documentNumber->value,
        ], array_slice($matches, $pagination->offset(), $pagination->size));

        return new Page($items, count($matches), $pagination);
    }

    public function findById(string $id): ?ResearcherDetails
    {
        foreach ($this->researchers as $researcher) {
            if ($researcher->id === $id) {
                $active = count(array_filter($this->lines, static fn (array $line): bool =>
                    $line['researcher_id'] === $id && $line['returned_at'] === null));
                return new ResearcherDetails($researcher, $active);
            }
        }

        return null;
    }
}
