<?php

declare(strict_types=1);

namespace Lab\Application\DTOs;

use JsonSerializable;

final readonly class Page implements JsonSerializable
{
    /** @param list<array<string, mixed>> $items */
    public function __construct(public array $items, public int $total, public Pagination $pagination) {}

    public function jsonSerialize(): array
    {
        return ['items' => $this->items, 'page' => $this->pagination->page,
            'size' => $this->pagination->size, 'total' => $this->total];
    }
}
