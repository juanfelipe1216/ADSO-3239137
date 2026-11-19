<?php

declare(strict_types=1);

namespace Lab\Adapters\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Lab\Domain\DomainViolation;

final class Input
{
    public static function invalid(): never
    {
        throw new DomainViolation('VALIDATION_ERROR', 'Revisa los campos, sus tipos y los valores permitidos.');
    }

    public static function body(Request $request, array $fields): array
    {
        $body = json_decode($request->getContent());
        if (!$body instanceof \stdClass || $request->query() !== []) {
            self::invalid();
        }
        $values = get_object_vars($body);
        if (array_diff(array_keys($values), $fields) || array_diff($fields, array_keys($values))) {
            self::invalid();
        }
        return $values;
    }

    public static function query(Request $request, array $fields = []): array
    {
        $values = $request->query();
        if (array_diff(array_keys($values), $fields) || !in_array(trim($request->getContent()), ['', '{}', '[]'], true)) {
            self::invalid();
        }
        return $values;
    }

    public static function text(mixed $value): string
    {
        if (!is_string($value)) {
            self::invalid();
        }
        return $value;
    }

    public static function integer(mixed $value, bool $query = false): int
    {
        if ($query && is_string($value) && preg_match('/\A[0-9]+\z/', $value) && filter_var($value, FILTER_VALIDATE_INT) !== false) {
            return (int) $value;
        }
        if (!is_int($value)) {
            self::invalid();
        }
        return $value;
    }

    public static function uuid(mixed $value): string
    {
        if (!is_string($value) || !Str::isUuid($value, 4)) {
            self::invalid();
        }
        return strtolower($value);
    }
}
