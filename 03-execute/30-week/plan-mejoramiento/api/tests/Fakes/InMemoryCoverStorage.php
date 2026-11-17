<?php

declare(strict_types=1);

namespace Tests\Fakes;

use Lab\Application\DTOs\CoverImage;
use Lab\Application\Ports\CoverStorage;

final class InMemoryCoverStorage implements CoverStorage
{
    public array $images = [];
    public array $deleted = [];
    private int $next = 0;

    public function put(CoverImage $image): string
    {
        $key = str_pad((string) ++$this->next, 64, '0', STR_PAD_LEFT);
        $this->images[$key] = $image;
        return $key;
    }
    public function read(string $key): ?CoverImage { return $this->images[$key] ?? null; }
    public function delete(string $key): void
    {
        $this->deleted[] = $key;
        unset($this->images[$key]);
    }
}
