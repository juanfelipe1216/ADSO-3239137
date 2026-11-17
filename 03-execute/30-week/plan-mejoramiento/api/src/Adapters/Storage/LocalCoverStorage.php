<?php

declare(strict_types=1);

namespace Lab\Adapters\Storage;

use Lab\Application\DTOs\CoverImage;
use Lab\Application\Ports\CoverStorage;
use RuntimeException;

final readonly class LocalCoverStorage implements CoverStorage
{
    public function __construct(private string $directory)
    {
        if ($directory === '') {
            throw new RuntimeException('El directorio de fotografías no está configurado.');
        }
    }

    public function put(CoverImage $image): string
    {
        if (!is_dir($this->directory) && !@mkdir($this->directory, 0700, true) && !is_dir($this->directory)) {
            throw new RuntimeException('No se pudo preparar el almacenamiento de fotografías.');
        }
        $key = bin2hex(random_bytes(32));
        $path = $this->path($key);
        $stream = @fopen($path, 'xb');
        if ($stream === false) {
            throw new RuntimeException('No se pudo guardar la fotografía.');
        }
        try {
            $offset = 0;
            while ($offset < strlen($image->bytes)) {
                $written = fwrite($stream, substr($image->bytes, $offset));
                if ($written === false || $written === 0) {
                    throw new RuntimeException('No se pudo completar la escritura de la fotografía.');
                }
                $offset += $written;
            }
        } catch (\Throwable $error) {
            fclose($stream);
            @unlink($path);
            throw $error;
        }
        fclose($stream);
        return $key;
    }

    public function read(string $key): ?CoverImage
    {
        $path = $this->path($key);
        if (!is_file($path) || is_link($path)) {
            return null;
        }
        $bytes = @file_get_contents($path, false, null, 0, CoverImage::MAX_BYTES + 1);
        if ($bytes === false) {
            throw new RuntimeException('No se pudo leer la fotografía.');
        }
        return new CoverImage($bytes);
    }

    public function delete(string $key): void
    {
        $path = $this->path($key);
        if (is_link($path)) {
            throw new RuntimeException('La clave de fotografía no es válida.');
        }
        if (is_file($path) && !@unlink($path)) {
            throw new RuntimeException('No se pudo eliminar la fotografía anterior.');
        }
    }

    private function path(string $key): string
    {
        if (!preg_match('/\A[a-f0-9]{64}\z/', $key)) {
            throw new RuntimeException('La clave de fotografía no es válida.');
        }
        return rtrim($this->directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $key;
    }
}
