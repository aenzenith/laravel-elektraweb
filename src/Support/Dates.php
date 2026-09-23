<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\Support;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Date handling for the `yyyy-MM-dd` strings the API expects.
 */
final class Dates
{
    public const FORMAT = 'Y-m-d';

    public static function toApi(DateTimeInterface|string $date): string
    {
        return self::toImmutable($date)->format(self::FORMAT);
    }

    public static function toImmutable(DateTimeInterface|string $date): DateTimeImmutable
    {
        if ($date instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($date);
        }

        $trimmed = trim($date);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Date string cannot be empty.');
        }

        $parsed = DateTimeImmutable::createFromFormat('!'.self::FORMAT, $trimmed);

        if ($parsed !== false && $parsed->format(self::FORMAT) === $trimmed) {
            return $parsed;
        }

        try {
            return new DateTimeImmutable($trimmed);
        } catch (\Exception $exception) {
            throw new InvalidArgumentException(sprintf('Could not parse date [%s].', $date), 0, $exception);
        }
    }

    public static function nullableToApi(DateTimeInterface|string|null $date): ?string
    {
        return $date === null ? null : self::toApi($date);
    }

    /**
     * Whole nights between two dates.
     */
    public static function nightsBetween(DateTimeInterface|string $from, DateTimeInterface|string $to): int
    {
        $start = self::toImmutable($from)->setTime(0, 0);
        $end = self::toImmutable($to)->setTime(0, 0);

        return (int) $start->diff($end)->format('%r%a');
    }
}
