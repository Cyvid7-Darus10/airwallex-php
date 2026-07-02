<?php

declare(strict_types=1);

namespace Airwallex;

use Airwallex\Exception\AirwallexException;
use Airwallex\Exception\ApiException;
use Airwallex\Exception\ConnectionException;
use Airwallex\Exception\RateLimitException;
use Airwallex\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Low-level HTTP layer: authentication, retries, and error mapping.
 *
 * Use {@see Client} instead; this class is not part of the public API.
 *
 * @internal
 */
final class ApiClient
{
    private readonly ClientInterface $http;
    private readonly TokenManager $tokenManager;

    /**
     * @var \Closure(float): void
     */
    private readonly \Closure $sleeper;

    /**
     * An injected PSR-18 client is used as-is — never reconfigured, mutated,
     * or closed — so callers keep full control of proxies, TLS, and timeouts.
     *
     * @param \Closure(float): void|null $sleeper test seam for retry delays
     */
    public function __construct(
        private readonly ClientConfig $config,
        ?ClientInterface $http = null,
        ?\Closure $sleeper = null,
    ) {
        $this->http = $http ?? new \GuzzleHttp\Client([
            'timeout' => $config->timeout,
            'connect_timeout' => $config->timeout,
            'http_errors' => false,
            'allow_redirects' => false,
        ]);
        $this->tokenManager = new TokenManager(
            $config->clientId,
            $config->apiKey,
            $config->baseUrl,
            $this->http,
        );
        $this->sleeper = $sleeper ?? static function (float $seconds): void {
            usleep((int) round($seconds * 1_000_000));
        };
    }

    public function config(): ClientConfig
    {
        return $this->config;
    }

    /**
     * Send an authenticated request, retrying transient failures.
     *
     * Transient failures (408/429/5xx and transport errors) are retried with
     * full-jitter exponential backoff, honouring Retry-After. The login
     * endpoint shares the same retry budget. A single 401 triggers one
     * re-login before failing. 409 business conflicts are never retried.
     *
     * @param array<string, mixed>|null $query
     * @param array<string, mixed>|null $body
     * @param array<string, string> $headers
     *
     * @throws ApiException
     * @throws ConnectionException
     */
    public function request(
        string $method,
        string $path,
        ?array $query = null,
        ?array $body = null,
        array $headers = [],
    ): mixed {
        $authRetried = false;
        $attempt = 0;

        while (true) {
            try {
                // The login endpoint shares this request's retry budget; its
                // failures back off without Retry-After because the response
                // object is not retained on exceptions (credential hygiene).
                $token = $this->tokenManager->getToken();
            } catch (ConnectionException $exception) {
                $this->backoffOrThrow($attempt, new ConnectionException(
                    \sprintf('Login failed after %d attempt(s): %s', $attempt + 1, $exception->getMessage()),
                    0,
                    $exception,
                ));
                ++$attempt;
                continue;
            } catch (ServerException|RateLimitException $exception) {
                $this->backoffOrThrow($attempt, $exception);
                ++$attempt;
                continue;
            }

            $request = $this->buildRequest($method, $path, $query, $body, $token, $headers);

            try {
                $response = $this->http->sendRequest($request);
            } catch (ClientExceptionInterface $exception) {
                $this->backoffOrThrow($attempt, new ConnectionException(
                    \sprintf('Request failed after %d attempt(s): %s', $attempt + 1, $exception->getMessage()),
                    0,
                    $exception,
                ));
                ++$attempt;
                continue;
            }

            $status = $response->getStatusCode();

            if ($status === 401 && !$authRetried) {
                $this->tokenManager->invalidate();
                $authRetried = true;
                continue;
            }

            if (\in_array($status, ClientConfig::RETRYABLE_STATUS_CODES, true)
                && $attempt < $this->config->maxRetries
            ) {
                ($this->sleeper)(self::retryDelay($attempt, $response));
                ++$attempt;
                continue;
            }

            if ($status >= 400) {
                throw ApiException::fromResponse($response);
            }

            return self::parseBody($response);
        }
    }

    /**
     * Throw $failure when the retry budget is exhausted; otherwise sleep the
     * jittered backoff delay so the caller can start the next attempt.
     *
     * @throws AirwallexException
     */
    private function backoffOrThrow(int $attempt, AirwallexException $failure): void
    {
        if ($attempt >= $this->config->maxRetries) {
            throw $failure;
        }
        ($this->sleeper)(self::retryDelay($attempt, null));
    }

    /**
     * @param array<string, mixed>|null $query
     */
    public function get(string $path, ?array $query = null): mixed
    {
        return $this->request('GET', $path, query: $query);
    }

    /**
     * @param array<string, mixed>|null $body
     * @param array<string, mixed>|null $query
     */
    public function post(string $path, ?array $body = null, ?array $query = null): mixed
    {
        return $this->request('POST', $path, query: $query, body: $body);
    }

    /**
     * @param array<string, mixed>|null $query
     * @param array<string, mixed>|null $body
     * @param array<string, string> $headers
     */
    private function buildRequest(
        string $method,
        string $path,
        ?array $query,
        ?array $body,
        string $token,
        array $headers,
    ): Request {
        $url = $this->config->baseUrl . $path;
        $cleanQuery = Util::cleanParams($query ?? []);
        if ($cleanQuery !== []) {
            $url .= (str_contains($path, '?') ? '&' : '?')
                . http_build_query($cleanQuery, '', '&', PHP_QUERY_RFC3986);
        }

        $requestHeaders = array_merge(
            $this->config->defaultHeaders(),
            ['Authorization' => 'Bearer ' . $token],
            $headers,
        );

        $payload = null;
        if ($body !== null) {
            $payload = json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $requestHeaders['Content-Type'] = 'application/json';
        }

        return new Request($method, $url, $requestHeaders, $payload);
    }

    /**
     * @throws ApiException when a 2xx body is not valid JSON (e.g. HTML from a proxy)
     */
    private static function parseBody(ResponseInterface $response): mixed
    {
        $raw = (string) $response->getBody();
        if ($raw === '') {
            return null;
        }

        try {
            return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $requestId = $response->getHeaderLine('x-request-id');

            throw new ApiException(
                \sprintf(
                    'Airwallex returned a %d response with an unparseable body (content-type: %s)',
                    $response->getStatusCode(),
                    $response->getHeaderLine('content-type') !== '' ? $response->getHeaderLine('content-type') : 'unknown',
                ),
                $response->getStatusCode(),
                null,
                null,
                $requestId !== '' ? $requestId : null,
            );
        }
    }

    /**
     * Full-jitter exponential backoff, honouring Retry-After when present.
     */
    private static function retryDelay(int $attempt, ?ResponseInterface $response): float
    {
        if ($response !== null) {
            $retryAfter = $response->getHeaderLine('retry-after');
            if ($retryAfter !== '') {
                $delay = self::parseRetryAfter($retryAfter);
                if ($delay !== null && $delay <= ClientConfig::MAX_RETRY_AFTER_SECONDS) {
                    return $delay;
                }
            }
        }

        $cap = min(
            ClientConfig::MAX_RETRY_DELAY_SECONDS,
            ClientConfig::INITIAL_RETRY_DELAY_SECONDS * (2 ** $attempt),
        );

        return $cap * random_int(0, PHP_INT_MAX) / PHP_INT_MAX;
    }

    /**
     * Parse a Retry-After header: either delta-seconds or an HTTP-date (RFC 7231).
     */
    private static function parseRetryAfter(string $value): ?float
    {
        if (is_numeric($value)) {
            return max(0.0, (float) $value);
        }

        $retryAt = strtotime($value);
        if ($retryAt === false) {
            return null;
        }

        return max(0.0, $retryAt - microtime(true));
    }
}
