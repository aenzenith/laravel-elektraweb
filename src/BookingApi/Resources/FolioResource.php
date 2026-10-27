<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Resources;

use Aenzenith\ElektraWeb\BookingApi\Data\FolioEntry;
use Aenzenith\ElektraWeb\BookingApi\Data\FolioTransaction;
use Aenzenith\ElektraWeb\BookingApi\Exceptions\InvalidRequestException;
use Aenzenith\ElektraWeb\BookingApi\Exceptions\RequestFailedException;
use Aenzenith\ElektraWeb\BookingApi\Requests\FolioPayment;
use Aenzenith\ElektraWeb\BookingApi\Requests\FolioRevenue;
use Illuminate\Support\Collection;

/**
 * "Folio" section: post payments and charges to a reservation's folio and read it back.
 *
 * Postings are not idempotent and the transport never retries them. When a posting fails with a
 * timeout it may still have been booked; read entries() before posting the same amount again.
 */
final class FolioResource extends HotelResource
{
    /**
     * POST /hotel/{id}/folio/add-payment
     *
     * @throws RequestFailedException when the provider rejects the posting
     */
    public function addPayment(FolioPayment $payment): FolioTransaction
    {
        return $this->post('folio/add-payment', $payment->toPayload());
    }

    /**
     * POST /hotel/{id}/folio/add-revenue
     *
     * @throws RequestFailedException when the provider rejects the posting
     */
    public function addRevenue(FolioRevenue $revenue): FolioTransaction
    {
        return $this->post('folio/add-revenue', $revenue->toPayload());
    }

    /**
     * GET /hotel/{id}/folio/details — every folio row of the given reservations.
     * Several ids are sent comma-separated in `reservation-ids`.
     *
     * @return Collection<int, FolioEntry>
     */
    public function entries(int ...$reservationIds): Collection
    {
        if ($reservationIds === []) {
            throw InvalidRequestException::because('At least one reservation id is required to read a folio.');
        }

        foreach ($reservationIds as $reservationId) {
            if ($reservationId < 1) {
                throw InvalidRequestException::because('Reservation id must be a positive integer.');
            }
        }

        $uri = $this->uri('folio/details');
        $response = $this->ensureSuccess(
            $this->connector->get($uri, ['reservation-ids' => implode(',', array_unique($reservationIds))]),
            'GET',
            $uri,
        );

        return (new Collection($this->expectList($response, $uri)))->map(FolioEntry::fromArray(...));
    }

    /**
     * GET /hotel/{id}/folio/definitions — reference lists for folio postings, cached.
     * The docs only show type 7 (board types). Row shapes differ per type, so rows are returned as-is.
     *
     * @return Collection<int, array<array-key, mixed>>
     */
    public function definitions(int $type): Collection
    {
        if ($type < 0) {
            throw InvalidRequestException::because('Folio definition type cannot be negative.');
        }

        $uri = $this->uri('folio/definitions');

        $rows = $this->cachedArray(
            'folio_definitions',
            $this->cacheKey((string) $type),
            fn () => $this->connector->get($uri, ['definition-type' => $type]),
        );

        return new Collection(array_values(array_filter($rows, 'is_array')));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function post(string $path, array $payload): FolioTransaction
    {
        $uri = $this->uri($path);

        return FolioTransaction::fromArray(
            $this->ensureSuccess($this->connector->post($uri, $payload), 'POST', $uri)->array()
        );
    }
}
