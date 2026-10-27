<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Data;

use Aenzenith\ElektraWeb\Support\ArrayReader;

/**
 * Answer of POST folio/add-payment and folio/add-revenue:
 * `{ "success": true, "message": "SP_RES_MAKE_PAYMENT_SUCCESS", "transaction-id": 470133231 }`
 */
final class FolioTransaction
{
    /**
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public readonly bool $success,
        public readonly ?string $message,
        public readonly ?int $transactionId,
        public readonly array $raw = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $r = ArrayReader::of($data);

        return new self(
            success: $r->bool('success', true),
            message: $r->nullableString('message'),
            transactionId: $r->nullableInt('transaction-id'),
            raw: $data,
        );
    }
}
