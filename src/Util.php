<?php

declare(strict_types=1);

namespace Airwallex;

/**
 * Internal helpers shared by the services and the transport layer.
 *
 * @internal
 */
final class Util
{
    private function __construct()
    {
    }

    /**
     * Percent-encode a resource id for safe URL-path interpolation.
     *
     * Prevents a malicious or malformed id (e.g. "../create") from routing
     * the request to a different endpoint.
     */
    public static function encodePathParam(string $value): string
    {
        return rawurlencode($value);
    }

    /**
     * Return a copy of the payload with a request_id present.
     *
     * Airwallex uses request_id for idempotency: retries of the same request
     * (including the SDK's automatic retries) must reuse the same id so a
     * payout or conversion is never executed twice. Generating it up front
     * makes every create call idempotent by default.
     *
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public static function ensureRequestId(array $payload): array
    {
        if (!empty($payload['request_id'])) {
            return $payload;
        }

        return array_merge($payload, ['request_id' => self::uuid4()]);
    }

    /**
     * Generate an RFC 4122 version-4 UUID from a CSPRNG.
     */
    public static function uuid4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = \chr((\ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = \chr((\ord($bytes[8]) & 0x3F) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    /**
     * Drop null values so optional query params are omitted, not sent as "".
     *
     * Booleans are stringified because RFC 3986 query encoding would
     * otherwise serialise false as "0"/"" which Airwallex rejects.
     *
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    public static function cleanParams(array $params): array
    {
        $clean = [];
        foreach ($params as $key => $value) {
            if ($value === null) {
                continue;
            }
            $clean[$key] = \is_bool($value) ? ($value ? 'true' : 'false') : $value;
        }

        return $clean;
    }
}
