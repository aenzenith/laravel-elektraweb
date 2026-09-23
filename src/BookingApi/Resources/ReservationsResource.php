<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Resources;

use Aenzenith\ElektraWeb\BookingApi\Data\DebtTotals;
use Aenzenith\ElektraWeb\BookingApi\Data\GuestUser;
use Aenzenith\ElektraWeb\BookingApi\Data\OperationResult;
use Aenzenith\ElektraWeb\BookingApi\Data\ReservationCreated;
use Aenzenith\ElektraWeb\BookingApi\Data\ReservationRecord;
use Aenzenith\ElektraWeb\BookingApi\Exceptions\InvalidRequestException;
use Aenzenith\ElektraWeb\BookingApi\Exceptions\RequestFailedException;
use Aenzenith\ElektraWeb\BookingApi\Requests\CreateReservation;
use Aenzenith\ElektraWeb\BookingApi\Requests\ReservationListFilter;
use Aenzenith\ElektraWeb\BookingApi\Requests\ServiceReservation;
use Aenzenith\ElektraWeb\BookingApi\Requests\UpdateReservation;
use Aenzenith\ElektraWeb\Support\Dates;
use DateTimeInterface;
use Illuminate\Support\Collection;

/**
 * "Reservation Operations" and the B2E reservation endpoints.
 */
final class ReservationsResource extends HotelResource
{
    /**
     * POST /hotel/{id}/createReservation
     *
     * @throws RequestFailedException when the provider rejects the booking
     */
    public function create(CreateReservation $reservation): ReservationCreated
    {
        $uri = $this->uri('createReservation');
        $response = $this->ensureSuccess(
            $this->connector->post($uri, $reservation->toPayload($this->hotelId)),
            'POST',
            $uri,
        );

        if ($response->get('reservation-id') === null) {
            throw RequestFailedException::fromResponse('POST', $uri, $response);
        }

        return ReservationCreated::fromArray($response->array());
    }

    /**
     * POST /hotel/{id}/updateReservation
     */
    public function update(UpdateReservation $reservation): OperationResult
    {
        $uri = $this->uri('updateReservation');

        return OperationResult::fromArray(
            $this->ensureSuccess($this->connector->post($uri, $reservation->toPayload($this->hotelId)), 'POST', $uri)->array()
        );
    }

    /**
     * POST /hotel/{id}/cancel-reservation (B2E)
     */
    public function cancel(int $reservationId): OperationResult
    {
        if ($reservationId < 1) {
            throw InvalidRequestException::because('Reservation id must be a positive integer.');
        }

        $uri = $this->uri('cancel-reservation');

        return OperationResult::fromArray(
            $this->ensureSuccess($this->connector->post($uri, ['reservation-id' => $reservationId]), 'POST', $uri)->array()
        );
    }

    /**
     * POST /hotel/{id}/createServiceReservation — attach extra services to a reservation.
     */
    public function addServices(ServiceReservation $services): OperationResult
    {
        $uri = $this->uri('createServiceReservation');

        return OperationResult::fromArray(
            $this->ensureSuccess($this->connector->post($uri, $services->toPayload()), 'POST', $uri)->array()
        );
    }

    /**
     * GET /hotel/{id}/reservation-list (B2E)
     *
     * @return Collection<int, ReservationRecord>
     */
    public function list(ReservationListFilter $filter): Collection
    {
        return $this->records($this->uri('reservation-list'), $filter->toQuery());
    }

    /**
     * GET /hotel/{id}/reservation-departure (B2E) — reservations leaving today (+ N days).
     *
     * @return Collection<int, ReservationRecord>
     */
    public function departures(int $daysUpFront = 0): Collection
    {
        if ($daysUpFront < 0) {
            throw InvalidRequestException::because('Days up front cannot be negative.');
        }

        return $this->records($this->uri('reservation-departure'), ['days-up-front' => $daysUpFront]);
    }

    /**
     * GET /hotel/{id}/reservation-in-house (B2E)
     *
     * @return Collection<int, ReservationRecord>
     */
    public function inHouse(): Collection
    {
        return $this->records($this->uri('reservation-in-house'));
    }

    /**
     * GET /hotel/{id}/reservation-debt-total (B2E)
     */
    public function debtTotals(DateTimeInterface|string $checkOutDate): DebtTotals
    {
        $uri = $this->uri('reservation-debt-total');
        $response = $this->connector->get($uri, ['check-out-date' => Dates::toApi($checkOutDate)]);

        return DebtTotals::fromArray($this->ensureSuccess($response, 'GET', $uri)->array());
    }

    /**
     * GET /hotel/{id}/find-guest-user (B2E) — null when no guest matches the email.
     */
    public function findGuestUser(string $email): ?GuestUser
    {
        $email = trim($email);

        if ($email === '') {
            throw InvalidRequestException::because('Email is required to find a guest user.');
        }

        $uri = $this->uri('find-guest-user');
        $response = $this->connector->request('GET', $uri, ['email' => $email], throw: false);

        if ($response->status() === 404 || $response->json() === null || $response->successFlag() === false) {
            return null;
        }

        if ($response->isHttpError()) {
            throw RequestFailedException::fromResponse('GET', $uri, $response);
        }

        $body = $response->isList() ? ($response->list()[0] ?? null) : $response->array();

        return is_array($body) && $body !== [] ? GuestUser::fromArray($body) : null;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return Collection<int, ReservationRecord>
     */
    private function records(string $uri, array $query = []): Collection
    {
        $response = $this->connector->get($uri, $query);

        if ($response->successFlag() === false) {
            return new Collection;
        }

        $rows = $response->isList()
            ? $response->list()
            : array_values(array_filter($response->reader()->array('reservation-list') ?: $response->reader()->array('data'), 'is_array'));

        return (new Collection($rows))->map(ReservationRecord::fromArray(...));
    }
}
