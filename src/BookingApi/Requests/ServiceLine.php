<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Requests;

use Aenzenith\ElektraWeb\BookingApi\Exceptions\InvalidRequestException;
use Aenzenith\ElektraWeb\Support\Dates;
use DateTimeInterface;

/**
 * One entry of createServiceReservation "services-list".
 */
final class ServiceLine
{
    private const MAX_EXTRA_QUESTIONS = 4;

    private ?int $unitCount = null;

    private ?int $adultCount = null;

    private ?int $childCount = null;

    private string $description = '';

    /**
     * @var list<array{question: string|null, answer: string|null}>
     */
    private array $extraQuestions = [];

    private function __construct(
        private readonly int $serviceId,
        private readonly string $date,
        private readonly float $price,
    ) {
        if ($price < 0) {
            throw InvalidRequestException::because('Service price cannot be negative.');
        }
    }

    public static function make(int $serviceId, DateTimeInterface|string $date, float $price): self
    {
        return new self($serviceId, Dates::toApi($date), $price);
    }

    /**
     * Quantity-based service (e.g. 2 airport transfers).
     */
    public function withUnits(int $unitCount): self
    {
        if ($unitCount < 1) {
            throw InvalidRequestException::because('Unit count must be at least 1.');
        }

        $copy = clone $this;
        $copy->unitCount = $unitCount;

        return $copy;
    }

    /**
     * Person-based service (e.g. 2 adults + 1 child for a tour).
     */
    public function withPax(int $adults, int $children = 0): self
    {
        if ($adults < 0 || $children < 0) {
            throw InvalidRequestException::because('Pax counts cannot be negative.');
        }

        $copy = clone $this;
        $copy->adultCount = $adults;
        $copy->childCount = $children;

        return $copy;
    }

    public function withDescription(string $description): self
    {
        $copy = clone $this;
        $copy->description = trim($description);

        return $copy;
    }

    /**
     * Answer one of the (up to four) extra questions attached to the service.
     */
    public function withAnswer(string $question, ?string $answer): self
    {
        if (count($this->extraQuestions) >= self::MAX_EXTRA_QUESTIONS) {
            throw InvalidRequestException::because('A service line supports at most four extra questions.');
        }

        $copy = clone $this;
        $copy->extraQuestions[] = ['question' => $question, 'answer' => $answer];

        return $copy;
    }

    public function price(): float
    {
        return $this->price;
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        $payload = [
            'service-id' => $this->serviceId,
            'adult-count' => $this->adultCount,
            'child-count' => $this->childCount,
            'unit-count' => $this->unitCount,
            'date' => $this->date,
            'price' => $this->price,
            'description' => $this->description,
        ];

        for ($i = 1; $i <= self::MAX_EXTRA_QUESTIONS; $i++) {
            $entry = $this->extraQuestions[$i - 1] ?? null;
            $payload['extra-question-'.$i] = $entry['question'] ?? null;
            $payload['extra-question-'.$i.'-answer'] = $entry['answer'] ?? null;
        }

        return $payload;
    }
}
