<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Data;

use Aenzenith\ElektraWeb\Support\ArrayReader;

/**
 * A folio row from GET /hotel/{id}/folio/details. The API answers with upper-case column names
 * (ID, RESID, DEPID, CTOTAL, ...); the most useful ones are mapped, the full row is kept in `raw`.
 *
 * `amount` is CTOTAL (posting currency), `localAmount` is MCTOTAL (hotel's main currency, CTOTAL x CURRATE).
 * In the documented example payments and rebates carry a negative total, charges a positive one.
 */
final class FolioEntry
{
    /**
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public readonly int $id,
        public readonly ?int $reservationId,
        public readonly ?int $departmentId,
        public readonly ?string $departmentName,
        public readonly ?int $revenueId,
        public readonly ?string $revenueName,
        public readonly float $amount,
        public readonly ?float $localAmount,
        public readonly ?string $currency,
        public readonly ?float $rate,
        public readonly ?string $note,
        public readonly ?string $transactionDate,
        public readonly ?string $createdAt,
        public readonly ?int $invoiceId,
        public readonly ?string $documentNo,
        public readonly ?string $guestName,
        public readonly ?string $userCode,
        public readonly array $raw = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $r = ArrayReader::of($data);

        return new self(
            id: $r->int('ID'),
            reservationId: $r->nullableInt('RESID'),
            departmentId: $r->nullableInt('DEPID'),
            departmentName: $r->nullableString('DEPARTMENTNAME'),
            revenueId: $r->nullableInt('REVID'),
            revenueName: $r->nullableString('REVENUENAME'),
            amount: $r->float('CTOTAL'),
            localAmount: $r->nullableFloat('MCTOTAL'),
            currency: ($currency = $r->nullableString('CURCODE')) !== null ? strtoupper($currency) : null,
            rate: $r->nullableFloat('CURRATE'),
            note: $r->nullableString('NOTE'),
            transactionDate: $r->nullableString('TDATE'),
            createdAt: $r->nullableString('CREATION_DATE'),
            invoiceId: $r->nullableInt('INVOICEID'),
            documentNo: $r->nullableString('DOCNO'),
            guestName: $r->nullableString('FULLNAME'),
            userCode: $r->nullableString('USERCODE'),
            raw: $data,
        );
    }

    public function get(string $path, mixed $default = null): mixed
    {
        return ArrayReader::of($this->raw)->get($path, $default);
    }
}
