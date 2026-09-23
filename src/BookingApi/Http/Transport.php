<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Http;

use Aenzenith\ElektraWeb\BookingApi\BookingApiConfig;
use Aenzenith\ElektraWeb\BookingApi\Exceptions\ConnectionFailedException;
use Aenzenith\ElektraWeb\Support\Payload;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use stdClass;
use Throwable;

/**
 * Thin, auth-agnostic wrapper around Laravel's HTTP client.
 *
 * Handles base URL, timeouts and transport retries. Never throws for HTTP
 * error statuses, only for failures to reach the provider at all.
 */
final class Transport
{
    public function __construct(
        private readonly Factory $http,
        private readonly BookingApiConfig $config,
    ) {}

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>|null  $json  null sends no body
     *
     * @throws ConnectionFailedException
     */
    public function send(string $method, string $uri, array $query = [], array $headers = [], ?array $json = null): ApiResponse
    {
        $uri = ltrim($uri, '/');
        $request = $this->pendingRequest()->withHeaders($headers);
        $options = [];

        if ($query !== []) {
            $options['query'] = Payload::query($query);
        }

        if ($json !== null) {
            // `{}` rather than `[]`: the API rejects an empty JSON array body.
            $options['json'] = $json === [] ? new stdClass : $json;
        }

        try {
            $response = $request->send(strtoupper($method), $uri, $options);
        } catch (ConnectionException $exception) {
            throw ConnectionFailedException::wrap($method, $uri, $exception);
        } catch (RequestException $exception) {
            // Only reachable when retries are exhausted on a 5xx; keep the response.
            return new ApiResponse($exception->response);
        } catch (Throwable $exception) {
            throw ConnectionFailedException::wrap($method, $uri, $exception);
        }

        return new ApiResponse($response);
    }

    private function pendingRequest(): PendingRequest
    {
        $request = $this->http
            ->baseUrl($this->config->baseUrl)
            ->acceptJson()
            ->timeout($this->config->timeout)
            ->withOptions(array_replace(
                ['connect_timeout' => $this->config->connectTimeout],
                $this->config->httpOptions,
            ));

        if ($this->config->retryTimes > 0) {
            $request = $request->retry(
                $this->config->retryTimes + 1,
                $this->config->retrySleepMs,
                static fn (Throwable $exception): bool => $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && $exception->response->serverError()),
            );
        }

        return $request;
    }
}
