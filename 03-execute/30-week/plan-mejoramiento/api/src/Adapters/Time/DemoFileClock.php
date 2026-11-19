<?php
declare(strict_types=1);
namespace Lab\Adapters\Time;

use DateTimeImmutable;
use DateTimeZone;
use Lab\Application\Ports\Clock;
use RuntimeException;

final readonly class DemoFileClock implements Clock
{
    public function __construct(private string $path, string $environment)
    {
        if ($environment !== 'testing' || $path === '') {
            throw new RuntimeException('El reloj de demostración requiere un entorno de pruebas aislado.');
        }
    }

    public function now(): DateTimeImmutable
    {
        $value = is_file($this->path) ? trim((string) file_get_contents($this->path)) : '';
        if ($value === 'system') {
            return new DateTimeImmutable('now', new DateTimeZone('UTC'));
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $value, new DateTimeZone('UTC'));
        if ($date === false || $date->format('Y-m-d\TH:i:s\Z') !== $value) {
            throw new RuntimeException('El archivo del reloj de demostración no es válido.');
        }
        return $date;
    }
}
