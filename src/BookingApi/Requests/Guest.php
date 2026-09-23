<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Requests;

use Aenzenith\ElektraWeb\BookingApi\Enums\Gender;
use Aenzenith\ElektraWeb\BookingApi\Enums\GuestTitle;
use Aenzenith\ElektraWeb\BookingApi\Exceptions\InvalidRequestException;
use Aenzenith\ElektraWeb\Support\Dates;
use Aenzenith\ElektraWeb\Support\Payload;
use DateTimeInterface;

/**
 * One entry of the reservation "guest-list".
 */
final class Guest
{
    private ?Gender $gender = null;

    private ?string $country = null;

    private ?string $birthday = null;

    private ?string $nationalNo = null;

    private ?string $passportNo = null;

    private ?string $email = null;

    private ?string $phone = null;

    private function __construct(
        private readonly GuestTitle $title,
        private readonly string $name,
        private readonly string $surname,
    ) {
        if (trim($name) === '' || trim($surname) === '') {
            throw InvalidRequestException::because('Guest name and surname are required.');
        }
    }

    public static function mr(string $name, string $surname): self
    {
        return (new self(GuestTitle::Mr, $name, $surname))->withGender(Gender::Male);
    }

    public static function ms(string $name, string $surname): self
    {
        return (new self(GuestTitle::Ms, $name, $surname))->withGender(Gender::Female);
    }

    public static function adult(string $name, string $surname, ?Gender $gender = null): self
    {
        return $gender === Gender::Female ? self::ms($name, $surname) : self::mr($name, $surname);
    }

    public static function child(string $name, string $surname, DateTimeInterface|string $birthday): self
    {
        return (new self(GuestTitle::Child, $name, $surname))->withBirthday($birthday);
    }

    public static function baby(string $name, string $surname, DateTimeInterface|string $birthday): self
    {
        return (new self(GuestTitle::Baby, $name, $surname))->withBirthday($birthday);
    }

    public static function withTitle(GuestTitle $title, string $name, string $surname): self
    {
        return new self($title, $name, $surname);
    }

    public function withGender(?Gender $gender): self
    {
        $copy = clone $this;
        $copy->gender = $gender;

        return $copy;
    }

    public function withCountry(?string $countryCode2): self
    {
        $copy = clone $this;
        $copy->country = $countryCode2 !== null && trim($countryCode2) !== '' ? strtoupper(trim($countryCode2)) : null;

        return $copy;
    }

    public function withBirthday(DateTimeInterface|string|null $birthday): self
    {
        $copy = clone $this;
        $copy->birthday = Dates::nullableToApi($birthday);

        return $copy;
    }

    public function withNationalNo(?string $nationalNo): self
    {
        $copy = clone $this;
        $copy->nationalNo = self::trimOrNull($nationalNo);

        return $copy;
    }

    public function withPassportNo(?string $passportNo): self
    {
        $copy = clone $this;
        $copy->passportNo = self::trimOrNull($passportNo);

        return $copy;
    }

    public function withEmail(?string $email): self
    {
        $copy = clone $this;
        $copy->email = self::trimOrNull($email);

        return $copy;
    }

    public function withPhone(?string $phone): self
    {
        $copy = clone $this;
        $copy->phone = Phone::normalize($phone);

        return $copy;
    }

    public function title(): GuestTitle
    {
        return $this->title;
    }

    public function name(): string
    {
        return trim($this->name);
    }

    public function surname(): string
    {
        return trim($this->surname);
    }

    public function isAdult(): bool
    {
        return $this->title->isAdult();
    }

    public function isChild(): bool
    {
        return $this->title === GuestTitle::Child;
    }

    public function isBaby(): bool
    {
        return $this->title === GuestTitle::Baby;
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        if ($this->title->requiresBirthday() && $this->birthday === null) {
            throw InvalidRequestException::because(sprintf(
                'Guest %s %s is a %s and needs a birthday.',
                $this->name(),
                $this->surname(),
                strtolower($this->title->name),
            ));
        }

        return Payload::withoutNulls([
            'title-id' => $this->title->value,
            'gender' => $this->gender?->value,
            'country' => $this->country,
            'name' => $this->name(),
            'surname' => $this->surname(),
            'birthday' => $this->birthday,
            'nationality-no' => $this->nationalNo,
            'passport-no' => $this->passportNo,
            'email' => $this->email,
            'phone' => $this->phone,
        ]);
    }

    private static function trimOrNull(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return trim($value);
    }
}
