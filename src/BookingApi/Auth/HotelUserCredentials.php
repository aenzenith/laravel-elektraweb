<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Auth;

/**
 * Login with hotel-id + usercode + password.
 */
final class HotelUserCredentials implements Credentials
{
    public function __construct(
        private readonly string $hotelId,
        private readonly string $usercode,
        private readonly string $password,
    ) {}

    public function fingerprint(): string
    {
        return 'hotel_user:'.hash('sha256', $this->hotelId.'|'.$this->usercode.'|'.$this->password);
    }

    public function headers(): array
    {
        return [];
    }

    public function payload(): array
    {
        return [
            'hotel-id' => $this->hotelId,
            'usercode' => $this->usercode,
            'password' => $this->password,
        ];
    }
}
