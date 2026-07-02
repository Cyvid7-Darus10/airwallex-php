<?php

declare(strict_types=1);

namespace Airwallex;

/**
 * Immutable configuration shared by the client and its transport layer.
 *
 * The API key is redacted from var_dump() output and serialized state so a
 * config object is always safe to log or ship to a monitoring service.
 *
 * @internal Constructed by {@see Client}; not part of the public API.
 */
final class ClientConfig
{
    public const DEFAULT_TIMEOUT_SECONDS = 60.0;
    public const DEFAULT_MAX_RETRIES = 2;
    public const INITIAL_RETRY_DELAY_SECONDS = 0.5;
    public const MAX_RETRY_DELAY_SECONDS = 8.0;

    /**
     * Retry-After values above this are ignored (jittered backoff is used
     * instead) so a hostile or misconfigured proxy cannot stall the caller.
     */
    public const MAX_RETRY_AFTER_SECONDS = 60.0;

    /**
     * 409 is deliberately absent: Airwallex uses it for business conflicts
     * (duplicate request_id, invalid state transitions) that must surface to
     * the caller as ConflictException, never be silently retried.
     */
    public const RETRYABLE_STATUS_CODES = [408, 429, 500, 502, 503, 504];

    private const INSECURE_HOSTS = ['localhost', '127.0.0.1', '::1', '[::1]'];

    public readonly string $baseUrl;

    public function __construct(
        public readonly string $clientId,
        #[\SensitiveParameter]
        public readonly string $apiKey,
        public readonly Env $env = Env::Production,
        ?string $baseUrl = null,
        public readonly ?string $apiVersion = null,
        public readonly ?string $onBehalfOf = null,
        public readonly float $timeout = self::DEFAULT_TIMEOUT_SECONDS,
        public readonly int $maxRetries = self::DEFAULT_MAX_RETRIES,
    ) {
        if ($clientId === '' || $apiKey === '') {
            throw new \InvalidArgumentException(
                'Both clientId and apiKey are required. Create API credentials in the '
                . 'Airwallex web app under Developer > API keys, or set the '
                . 'AIRWALLEX_CLIENT_ID / AIRWALLEX_API_KEY environment variables.',
            );
        }
        if ($maxRetries < 0) {
            throw new \InvalidArgumentException('maxRetries must be >= 0');
        }
        if ($timeout <= 0) {
            throw new \InvalidArgumentException('timeout must be > 0');
        }
        $this->baseUrl = self::validateBaseUrl(rtrim($baseUrl ?? $env->baseUrl(), '/'));
    }

    /**
     * Require HTTPS so credentials are never sent in cleartext.
     *
     * Plain HTTP is allowed only for loopback hosts (local mocks and tests).
     */
    private static function validateBaseUrl(string $baseUrl): string
    {
        $scheme = strtolower((string) parse_url($baseUrl, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($baseUrl, PHP_URL_HOST));
        // parse_url accepts host-less URLs like "https:evil.com" (scheme +
        // path only); require a real host so validation fails at boot, not
        // with a confusing connection error at request time.
        if ($scheme === 'https' && $host !== '') {
            return $baseUrl;
        }
        if ($scheme === 'http' && \in_array($host, self::INSECURE_HOSTS, true)) {
            return $baseUrl;
        }

        throw new \InvalidArgumentException(
            \sprintf('baseUrl must be a https URL with a host (got "%s"); plain http is only allowed for localhost', $baseUrl),
        );
    }

    /**
     * Headers applied to every authenticated request.
     *
     * @return array<string, string>
     */
    public function defaultHeaders(): array
    {
        $headers = [
            'User-Agent' => 'airwallex-php/' . Client::VERSION,
            'Accept' => 'application/json',
        ];
        if ($this->apiVersion !== null) {
            $headers['x-api-version'] = $this->apiVersion;
        }
        if ($this->onBehalfOf !== null) {
            $headers['x-on-behalf-of'] = $this->onBehalfOf;
        }

        return $headers;
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
            'env' => $this->env,
            'baseUrl' => $this->baseUrl,
            'apiVersion' => $this->apiVersion,
            'onBehalfOf' => $this->onBehalfOf,
            'timeout' => $this->timeout,
            'maxRetries' => $this->maxRetries,
        ];
    }
}
