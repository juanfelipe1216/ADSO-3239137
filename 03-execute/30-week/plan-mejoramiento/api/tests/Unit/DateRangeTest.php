<?php

declare(strict_types=1);

namespace Tests\Unit;

use Lab\Domain\DomainViolation;
use Lab\Domain\ValueObjects\DateRange;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DateRangeTest extends TestCase
{
    public function test_inclusive_day_is_represented_as_a_half_open_utc_interval(): void
    {
        $range = new DateRange('2026-11-14', '2026-11-14');
        self::assertSame('2026-11-14T00:00:00+00:00', $range->from->format(DATE_ATOM));
        self::assertSame('2026-11-15T00:00:00+00:00', $range->exclusiveEnd()->format(DATE_ATOM));
    }

    public function test_reversed_range_has_a_specific_error(): void
    {
        try {
            new DateRange('2026-11-15', '2026-11-14');
            self::fail('Expected invalid range');
        } catch (DomainViolation $error) {
            self::assertSame('INVALID_DATE_RANGE', $error->errorCode);
        }
    }

    #[DataProvider('invalidDates')]
    public function test_invalid_calendar_dates_are_not_silently_corrected(string $value): void
    {
        $this->expectException(DomainViolation::class);
        new DateRange($value, '2026-12-31');
    }

    public static function invalidDates(): array { return [['2026-02-29'], ['2026-13-01'], ['14/11/2026'], ['2026-1-1']]; }
}
