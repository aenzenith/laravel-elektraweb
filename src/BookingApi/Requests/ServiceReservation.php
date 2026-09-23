<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Requests;

use Aenzenith\ElektraWeb\BookingApi\Exceptions\InvalidRequestException;

/**
 * Body for POST /hotel/{id}/createServiceReservation
 */
final class ServiceReservation
{
    /**
     * @var list<ServiceLine>
     */
    private array $lines = [];

    private ?float $totalPrice = null;

    private function __construct(
        private readonly int $reservationId,
        private readonly string $currency,
    ) {
        if ($reservationId < 1) {
            throw InvalidRequestException::because('Reservation id must be a positive integer.');
        }
    }

    public static function for(int $reservationId, string $currency): self
    {
        return new self($reservationId, strtoupper(trim($currency)));
    }

    public function addLine(ServiceLine $line): self
    {
        $copy = clone $this;
        $copy->lines[] = $line;

        return $copy;
    }

    /**
     * @param  iterable<mixed>  $lines
     */
    public function withLines(iterable $lines): self
    {
        $list = [];

        foreach ($lines as $line) {
            if (! $line instanceof ServiceLine) {
                throw InvalidRequestException::because('Service lines must be ServiceLine objects.');
            }

            $list[] = $line;
        }

        $copy = clone $this;
        $copy->lines = $list;

        return $copy;
    }

    /**
     * Override the total. By default it is the sum of line prices.
     */
    public function withTotalPrice(?float $totalPrice): self
    {
        $copy = clone $this;
        $copy->totalPrice = $totalPrice;

        return $copy;
    }

    public function totalPrice(): float
    {
        return $this->totalPrice ?? (float) array_sum(array_map(static fn (ServiceLine $line): float => $line->price(), $this->lines));
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        if ($this->lines === []) {
            throw InvalidRequestException::because('Service reservation needs at least one service line.');
        }

        return [
            'res-id' => $this->reservationId,
            'total-price' => $this->totalPrice(),
            'currency' => $this->currency,
            'services-list' => array_map(static fn (ServiceLine $line): array => $line->toPayload(), $this->lines),
        ];
    }
}
