<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Requests;

use Aenzenith\ElektraWeb\BookingApi\Exceptions\InvalidRequestException;
use Aenzenith\ElektraWeb\Support\Dates;
use DateTimeInterface;

/**
 * Query for GET /hotel/{id}/availability
 */
final class AvailabilitySearch
{
    private function __construct(
        public readonly string $from,
        public readonly string $to,
    ) {}

    public static function between(DateTimeInterface|string $from, DateTimeInterface|string $to): self
    {
        $fromDate = Dates::toApi($from);
        $toDate = Dates::toApi($to);

        if ($toDate < $fromDate) {
            throw InvalidRequestException::because('Availability range end must not be before its start.');
        }

        return new self($fromDate, $toDate);
    }

    /**
     * @return array<string, string>
     */
    public function toQuery(): array
    {
        return [
            'fromdate' => $this->from,
            'todate' => $this->to,
        ];
    }
}
