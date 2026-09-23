<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Http;

use Aenzenith\ElektraWeb\BookingApi\Auth\Authenticator;
use Aenzenith\ElektraWeb\BookingApi\Events\RequestFailed;
use Aenzenith\ElektraWeb\BookingApi\Exceptions\BookingApiException;
use Aenzenith\ElektraWeb\BookingApi\Exceptions\ConnectionFailedException;
use Aenzenith\ElektraWeb\BookingApi\Exceptions\NotConfiguredException;
use Aenzenith\ElektraWeb\BookingApi\Exceptions\RequestFailedException;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Authenticated gateway used by every resource.
 *
 * Adds identity headers (JWT / x-captcha), refreshes the token once on 401
 * and turns HTTP errors into {@see RequestFailedException}.
 */
final class Connector
{
    public function __construct(
        private readonly Transport $transport,
        private readonly Authenticator $authenticator,
        private readonly RequestOptions $options,
        private readonly ?Dispatcher $events = null,
    ) {}

    public function options(): RequestOptions
    {
        return $this->options;
    }

    public function withOptions(RequestOptions $options): self
    {
        return new self($this->transport, $this->authenticator, $options, $this->events);
    }

    /**
     * @param  array<string, mixed>  $query
     *
     * @throws RequestFailedException|ConnectionFailedException
     */
    public function get(string $uri, array $query = [], bool $authenticate = true): ApiResponse
    {
        return $this->request('GET', $uri, $query, null, $authenticate);
    }

    /**
     * @param  array<string, mixed>  $json
     *
     * @throws RequestFailedException|ConnectionFailedException
     */
    public function post(string $uri, array $json = [], bool $authenticate = true): ApiResponse
    {
        return $this->request('POST', $uri, [], $json, $authenticate);
    }

    /**
     * Same as get()/post() but returns the response even when it is an HTTP error.
     *
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>|null  $json
     */
    public function request(string $method, string $uri, array $query = [], ?array $json = null, bool $authenticate = true, bool $throw = true): ApiResponse
    {
        try {
            $response = $this->dispatch($method, $uri, $query, $json, $authenticate);

            if ($throw && $response->isHttpError()) {
                throw RequestFailedException::fromResponse($method, $uri, $response);
            }

            return $response;
        } catch (BookingApiException $exception) {
            $this->events?->dispatch(new RequestFailed($method, $uri, $exception));

            throw $exception;
        }
    }

    /**
     * Force a fresh login on the next authenticated call.
     */
    public function forgetToken(): void
    {
        if ($this->options->credentials !== null) {
            $this->authenticator->forget($this->options->credentials);
        }
    }

    public function authenticator(): Authenticator
    {
        return $this->authenticator;
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>|null  $json
     */
    private function dispatch(string $method, string $uri, array $query, ?array $json, bool $authenticate): ApiResponse
    {
        $headers = array_replace($this->options->headers, $this->options->identityHeaders());
        $credentials = $this->options->credentials;

        if (! $authenticate || $credentials === null) {
            if ($authenticate && $this->options->captcha === null) {
                throw NotConfiguredException::missing('auth');
            }

            return $this->transport->send($method, $uri, $query, $headers, $json);
        }

        $token = $this->authenticator->token($credentials);
        $response = $this->transport->send($method, $uri, $query, $headers + ['Authorization' => $token->authorizationHeader()], $json);

        if (! $response->isUnauthorized()) {
            return $response;
        }

        // Token revoked or expired server-side: log in again exactly once.
        $this->authenticator->forget($credentials);
        $token = $this->authenticator->login($credentials);

        return $this->transport->send($method, $uri, $query, $headers + ['Authorization' => $token->authorizationHeader()], $json);
    }
}
