<?php

declare(strict_types=1);

namespace Tests\Fakes;

use DateTimeImmutable;
use Lab\Application\DTOs\OverdueLine;
use Lab\Application\Ports\OverdueReader;

final readonly class InMemoryOverdueReader implements OverdueReader
{
    public function __construct(private InMemoryLoanRepository $loans, private InMemoryResearcherReader $researchers) {}

    public function findOverdue(DateTimeImmutable $now): array
    {
        $rows = [];
        foreach ($this->loans->rows as $loan) {
            foreach ($loan->items as $item) {
                if ($item->isOverdue($now)) {
                    $researcher = $this->researchers->findById($loan->researcherId)
                        ?? throw new \LogicException('Missing researcher in loan fixture.');
                    $rows[] = new OverdueLine($loan->id, $researcher->researcher->fullName, $item);
                }
            }
        }
        usort($rows, static fn (OverdueLine $left, OverdueLine $right): int =>
            [$left->item->dueAt, $left->item->id] <=> [$right->item->dueAt, $right->item->id]);
        return $rows;
    }
}
