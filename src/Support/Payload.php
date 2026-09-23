<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\Support;

/**
 * Helpers for building outgoing JSON / query payloads.
 */
final class Payload
{
    /**
     * Drop nulls so optional fields are simply omitted.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function withoutNulls(array $payload): array
    {
        return array_filter($payload, static fn (mixed $value): bool => $value !== null);
    }

    /**
     * Query strings cannot carry PHP booleans, the API expects literal true/false.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, scalar>
     */
    public static function query(array $query): array
    {
        $result = [];

        foreach ($query as $key => $value) {
            if ($value === null) {
                $result[$key] = '';

                continue;
            }

            if (is_bool($value)) {
                $result[$key] = $value ? 'true' : 'false';

                continue;
            }

            if (is_array($value)) {
                $result[$key] = implode(',', array_map('strval', $value));

                continue;
            }

            $result[$key] = is_scalar($value) ? $value : (string) $value;
        }

        return $result;
    }
}
