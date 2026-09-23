<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Auth;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * A JWT issued by POST /login.
 */
final class AccessToken
{
    public function __construct(
        public readonly string $value,
        public readonly ?DateTimeImmutable $expiresAt = null,
    ) {}

    /**
     * Build from a raw token, reading `exp` from the JWT payload when present.
     */
    public static function fromString(string $token, ?DateTimeInterface $fallbackExpiry = null): self
    {
        $expiresAt = self::expiryFromJwt($token)
            ?? ($fallbackExpiry !== null ? DateTimeImmutable::createFromInterface($fallbackExpiry) : null);

        return new self($token, $expiresAt);
    }

    public static function looksLikeJwt(string $candidate): bool
    {
        return preg_match('/^[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]*$/', $candidate) === 1;
    }

    public function isExpired(?DateTimeInterface $now = null, int $leewaySeconds = 30): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        $now ??= new DateTimeImmutable;

        return $this->expiresAt->getTimestamp() - $leewaySeconds <= $now->getTimestamp();
    }

    /**
     * Seconds until expiry, or null when the token has no known expiry.
     */
    public function secondsUntilExpiry(?DateTimeInterface $now = null): ?int
    {
        if ($this->expiresAt === null) {
            return null;
        }

        $now ??= new DateTimeImmutable;

        return max(0, $this->expiresAt->getTimestamp() - $now->getTimestamp());
    }

    public function authorizationHeader(): string
    {
        return 'Bearer '.$this->value;
    }

    /**
     * @return array{value: string, expires_at: int|null}
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'expires_at' => $this->expiresAt?->getTimestamp(),
        ];
    }

    /**
     * @param  array{value?: mixed, expires_at?: mixed}  $data
     */
    public static function fromArray(array $data): ?self
    {
        $value = $data['value'] ?? null;

        if (! is_string($value) || $value === '') {
            return null;
        }

        $expiresAt = $data['expires_at'] ?? null;

        return new self(
            $value,
            is_numeric($expiresAt) ? (new DateTimeImmutable)->setTimestamp((int) $expiresAt) : null,
        );
    }

    private static function expiryFromJwt(string $token): ?DateTimeImmutable
    {
        $parts = explode('.', $token);

        if (count($parts) < 2) {
            return null;
        }

        $payload = base64_decode(strtr($parts[1], '-_', '+/'), true);

        if ($payload === false) {
            return null;
        }

        $claims = json_decode($payload, true);

        if (! is_array($claims) || ! isset($claims['exp']) || ! is_numeric($claims['exp'])) {
            return null;
        }

        return (new DateTimeImmutable)->setTimestamp((int) $claims['exp']);
    }
}
