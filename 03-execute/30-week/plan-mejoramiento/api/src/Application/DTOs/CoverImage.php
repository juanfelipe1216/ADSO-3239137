<?php

declare(strict_types=1);

namespace Lab\Application\DTOs;

use Lab\Domain\DomainViolation;

final readonly class CoverImage
{
    public const MAX_BYTES = 2097152;
    public string $mimeType;

    public function __construct(public string $bytes)
    {
        if ($bytes === '' || strlen($bytes) > self::MAX_BYTES) {
            throw new DomainViolation('VALIDATION_ERROR', 'La fotografía debe tener contenido y no superar 2 MiB.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        $dimensions = @getimagesizefromstring($bytes);
        if (!in_array($mime, ['image/jpeg', 'image/png'], true) || $dimensions === false
            || $dimensions[0] < 1 || $dimensions[1] < 1 || ($dimensions['mime'] ?? null) !== $mime) {
            throw new DomainViolation('VALIDATION_ERROR', 'La fotografía debe ser una imagen JPG o PNG válida.');
        }
        $this->mimeType = $mime;
    }
}
