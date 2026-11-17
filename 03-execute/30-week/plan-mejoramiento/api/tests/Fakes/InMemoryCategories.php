<?php

declare(strict_types=1);

namespace Tests\Fakes;

use Lab\Application\Ports\CategoryReader;

final readonly class InMemoryCategories implements CategoryReader
{
    /** @param array<string, string> $categories */
    public function __construct(private array $categories) {}

    public function findName(string $id): ?string { return $this->categories[$id] ?? null; }

    public function all(): array
    {
        $categories = $this->categories;
        asort($categories, SORT_STRING);
        $rows = [];
        foreach ($categories as $id => $name) {
            $rows[] = ['id' => $id, 'name' => $name];
        }
        return $rows;
    }
}
