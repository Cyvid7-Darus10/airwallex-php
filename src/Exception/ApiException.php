<?php

declare(strict_types=1);

namespace Airwallex\Exception;

use Psr\Http\Message\ResponseInterface;

/**
 * The API responded with a non-success HTTP status code.
 *
 * Carries the Airwallex error envelope ({code, message, source}) plus the
 * x-request-id response header — include the request id when contacting
 * Airwallex support. The exception never holds credentials or the
 * Authorization header, so it is safe to log as-is.
 */
class ApiException extends AirwallexException
{
    /**
     * @param mixed $body Parsed JSON body of the error response, if any.
     */
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly ?string $errorCode = null,
        public readonly ?string $source = null,
        public readonly ?string $requestId = null,
        public readonly mixed $body = null,
    ) {
        parent::__construct(self::composeMessage($message, $statusCode, $errorCode, $source, $requestId));
    }

    /**
     * Build the most specific exception type for an HTTP error response.
     *
     * The response body is parsed as the Airwallex error envelope
     * {code, message, source}; non-JSON bodies fall back to a text snippet.
     */
    public static function fromResponse(ResponseInterface $response): self
    {
        $status = $response->getStatusCode();
        $raw = (string) $response->getBody();
        $body = json_decode($raw, true);

        if (\is_array($body)) {
            $rawMessage = $body['message'] ?? $body['error'] ?? null;
            $message = \is_scalar($rawMessage) ? (string) $rawMessage : $response->getReasonPhrase();
            $code = isset($body['code']) && \is_scalar($body['code']) ? (string) $body['code'] : null;
            $source = isset($body['source']) && \is_scalar($body['source']) ? (string) $body['source'] : null;
        } else {
            $body = null;
            $message = mb_substr($raw, 0, 200);
            if ($message === '') {
                $message = $response->getReasonPhrase();
            }
            $code = null;
            $source = null;
        }

        $requestId = $response->getHeaderLine('x-request-id');
        $class = self::classForStatus($status);

        return new $class($message, $status, $code, $source, $requestId !== '' ? $requestId : null, $body);
    }

    /**
     * @return class-string<self>
     */
    private static function classForStatus(int $status): string
    {
        return match (true) {
            $status === 400 => BadRequestException::class,
            $status === 401 => AuthenticationException::class,
            $status === 403 => PermissionDeniedException::class,
            $status === 404 => NotFoundException::class,
            $status === 409 => ConflictException::class,
            $status === 429 => RateLimitException::class,
            $status >= 500 => ServerException::class,
            default => self::class,
        };
    }

    private static function composeMessage(
        string $message,
        int $statusCode,
        ?string $errorCode,
        ?string $source,
        ?string $requestId,
    ): string {
        $parts = [\sprintf('[%d] %s', $statusCode, $message)];
        if ($errorCode !== null && $errorCode !== '') {
            $parts[] = 'code=' . $errorCode;
        }
        if ($source !== null && $source !== '') {
            $parts[] = 'source=' . $source;
        }
        if ($requestId !== null && $requestId !== '') {
            $parts[] = 'request_id=' . $requestId;
        }

        return implode(' ', $parts);
    }
}
