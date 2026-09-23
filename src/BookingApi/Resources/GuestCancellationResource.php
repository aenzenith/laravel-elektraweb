<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Resources;

use Aenzenith\ElektraWeb\BookingApi\Data\GuestReservationLookupResult;
use Aenzenith\ElektraWeb\BookingApi\Data\OperationResult;
use Aenzenith\ElektraWeb\BookingApi\Exceptions\InvalidRequestException;
use Aenzenith\ElektraWeb\BookingApi\Requests\GuestReservationLookup;

/**
 * "Cancel Reservation By SMS Verification" — the public, captcha-protected flow
 * a guest uses to cancel their own booking.
 *
 * These endpoints are documented as `noauth`; a captcha token set through
 * BookingApi::withCaptcha() is forwarded, and so is a JWT when credentials exist.
 */
final class GuestCancellationResource extends HotelResource
{
    /**
     * Step 1 — POST /hotel/{id}/findGuestReservation
     *
     * Sends an SMS when exactly one reservation matches; otherwise returns the
     * candidates so the guest can pick one and call again with its reservation number.
     */
    public function find(GuestReservationLookup $lookup): GuestReservationLookupResult
    {
        $response = $this->connector->post(
            $this->uri('findGuestReservation'),
            $lookup->toPayload(),
            authenticate: $this->connector->options()->credentials !== null,
        );

        return GuestReservationLookupResult::fromArray($response->array());
    }

    /**
     * Step 2 — POST /hotel/{id}/cancelGuestReservation
     */
    public function confirm(string $smsCheckUid, string $smsCode): OperationResult
    {
        if (trim($smsCheckUid) === '' || trim($smsCode) === '') {
            throw InvalidRequestException::because('SMS check uid and SMS code are both required.');
        }

        $uri = $this->uri('cancelGuestReservation');

        $response = $this->connector->post(
            $uri,
            ['sms-check-uid' => trim($smsCheckUid), 'sms-code' => trim($smsCode)],
            authenticate: $this->connector->options()->credentials !== null,
        );

        return OperationResult::fromArray($this->ensureSuccess($response, 'POST', $uri)->array());
    }
}
