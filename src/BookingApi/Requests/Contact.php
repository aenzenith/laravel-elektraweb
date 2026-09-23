<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Requests;

use Aenzenith\ElektraWeb\Support\Payload;

/**
 * Reservation contact person ("contact-*" fields).
 */
final class Contact
{
    public function __construct(
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
    ) {}

    public static function make(string $firstName, string $lastName, ?string $email = null, ?string $phone = null): self
    {
        return new self(
            trim($firstName),
            trim($lastName),
            $email !== null && trim($email) !== '' ? trim($email) : null,
            Phone::normalize($phone),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return Payload::withoutNulls([
            'contact-first-name' => $this->firstName,
            'contact-last-name' => $this->lastName,
            'contact-email' => $this->email,
            'contact-phone' => $this->phone,
        ]);
    }
}
