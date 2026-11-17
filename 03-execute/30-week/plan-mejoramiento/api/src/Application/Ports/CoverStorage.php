<?php

declare(strict_types=1);

namespace Lab\Application\Ports;

use Lab\Application\DTOs\CoverImage;

interface CoverStorage
{
    /** Store under a fresh opaque key; never overwrite an existing image. */
    public function put(CoverImage $image): string;
    public function read(string $key): ?CoverImage;
    /** Removing an absent key is idempotent. */
    public function delete(string $key): void;
}
