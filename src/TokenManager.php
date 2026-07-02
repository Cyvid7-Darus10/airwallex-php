<?php

declare(strict_types=1);

namespace Airwallex;

use Airwallex\Exception\ApiException;
use Airwallex\Exception\AuthenticationException;
use Airwallex\Exception\ConnectionException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Fetches and caches the bearer token.
 *
 * The token is fetched lazily on the first request and reused across every
 * call made by the same client instance (important for long-lived processes
 * such as queue workers). It is refreshed {@see self::REFRESH_LEEWAY_SECONDS}
 * before its ~30-minute expiry so an in-flight request never carries a token
 * that expires mid-request.
 *
 * @internal
 */
final class TokenManager
{
    public const LOGIN_PATH = '/api/v1/authentication/login';

    /**
     * Refresh this many seconds before expiry.
     */
    public const REFRESH_LEEWAY_SECONDS = 60.0;

    /**
     * Fallback TTL when the API returns an unparseable expiry; Airwallex
     * tokens are documented to last 30 minutes.
     */
    private const FALLBACK_TTL_SECONDS = 30 * 60;

    private const EXPIRES_AT_FORMATS = [
        'Y-m-d\TH:i:sO',      // 2026-01-01T00:00:00+0000 (documented format)
        'Y-m-d\TH:i:sP',      // 2026-01-01T00:00:00+00:00
        'Y-m-d\TH:i:s.uO',
        'Y-m-d\TH:i:s.uP',
    ];

    private ?string $token = null;
    private float $expiresAt = 0.0;

    public function __construct(
        private readonly string $clientId,
        #[\SensitiveParameter]
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly ClientInterface $http,
    ) {
    }

    /**
     * Return a valid bearer token, logging in if the cached one is missing or stale.
     *
     * @throws ConnectionException when the login request never completes
     * @throws ApiException when the login endpoint returns an error status
     * @throws AuthenticationException when the login body is unusable
     */
    public function getToken(): string
    {
        if ($this->token !== null && $this->isFresh()) {
            return $this->token;
        }

        $request = new Request('POST', $this->baseUrl . self::LOGIN_PATH, [
            'x-client-id' => $this->clientId,
            'x-api-key' => $this->apiKey,
            'Accept' => 'application/json',
            'User-Agent' => 'airwallex-php/' . Client::VERSION,
        ]);

        try {
            $response = $this->http->sendRequest($request);
        } catch (ClientExceptionInterface $exception) {
            throw new ConnectionException(
                'Login request failed: ' . $exception->getMessage(),
                0,
                $exception,
            );
        }

        return $this->store($response);
    }

    /**
     * Drop the cached token so the next call re-authenticates.
     */
    public function invalidate(): void
    {
        $this->token = null;
        $this->expiresAt = 0.0;
    }

    private function isFresh(): bool
    {
        return microtime(true) < $this->expiresAt - self::REFRESH_LEEWAY_SECONDS;
    }

    private function store(ResponseInterface $response): string
    {
        if ($response->getStatusCode() >= 400) {
            throw ApiException::fromResponse($response);
        }

        $body = json_decode((string) $response->getBody(), true);
        if (!\is_array($body)) {
            throw new AuthenticationException(
                \sprintf(
                    'Login returned a %d response with an unparseable body (is a proxy intercepting the request?)',
                    $response->getStatusCode(),
                ),
                $response->getStatusCode(),
                null,
                null,
                $response->getHeaderLine('x-request-id') !== '' ? $response->getHeaderLine('x-request-id') : null,
            );
        }

        $token = $body['token'] ?? null;
        if (!\is_string($token) || $token === '') {
            throw new AuthenticationException(
                'Login succeeded but no token was returned',
                $response->getStatusCode(),
            );
        }

        $this->token = $token;
        $this->expiresAt = self::parseExpiresAt($body['expires_at'] ?? null);

        return $token;
    }

    /**
     * Convert the login response expires_at into a unix timestamp.
     */
    private static function parseExpiresAt(mixed $raw): float
    {
        if (!\is_string($raw) || $raw === '') {
            return microtime(true) + self::FALLBACK_TTL_SECONDS;
        }

        foreach (self::EXPIRES_AT_FORMATS as $format) {
            $parsed = \DateTimeImmutable::createFromFormat($format, $raw, new \DateTimeZone('UTC'));
            if ($parsed !== false) {
                return (float) $parsed->getTimestamp();
            }
        }

        // Zulu / other ISO-8601 variants.
        $timestamp = strtotime($raw);
        if ($timestamp !== false) {
            return (float) $timestamp;
        }

        return microtime(true) + self::FALLBACK_TTL_SECONDS;
    }

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return $this->redacted();
    }

    /**
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return $this->redacted();
    }

    /**
     * @return array<string, mixed>
     */
    private function redacted(): array
    {
        return [
            'clientId' => $this->clientId,
            'apiKey' => '[REDACTED]',
            'token' => $this->token === null ? null : '[REDACTED]',
            'baseUrl' => $this->baseUrl,
            'expiresAt' => $this->expiresAt,
        ];
    }
}
