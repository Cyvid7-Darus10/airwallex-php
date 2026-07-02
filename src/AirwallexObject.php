<?php

declare(strict_types=1);

namespace Airwallex;

use Airwallex\Exception\AirwallexException;

/**
 * Base class for all API response objects.
 *
 * Instances are immutable and keep the full decoded payload, so fields added
 * by newer API versions are preserved (accessible via property access or
 * {@see toArray()}) rather than dropped — the SDK stays forward-compatible.
 *
 * @implements \ArrayAccess<string, mixed>
 */
class AirwallexObject implements \ArrayAccess, \JsonSerializable
{
    /**
     * @param array<string, mixed> $data
     */
    final public function __construct(private readonly array $data = [])
    {
    }

    /**
     * Build an instance from a decoded API payload.
     *
     * A non-array payload (a JSON scalar or literal null where a resource was
     * expected) throws rather than silently producing an empty object, so a
     * misbehaving proxy or API regression surfaces immediately.
     *
     * @throws AirwallexException when the payload is not a JSON object
     */
    public static function make(mixed $data): static
    {
        if (!\is_array($data)) {
            throw new AirwallexException(\sprintf(
                'Expected a JSON object for %s, got %s',
                static::class,
                get_debug_type($data),
            ));
        }

        $normalized = [];
        foreach ($data as $key => $value) {
            $normalized[(string) $key] = $value;
        }

        return new static($normalized);
    }

    public function __get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }

    public function __isset(string $name): bool
    {
        return isset($this->data[$name]);
    }

    public function __set(string $name, mixed $value): void
    {
        throw new \LogicException(
            static::class . ' is immutable; API response objects cannot be modified.',
        );
    }

    /**
     * The full decoded payload as returned by the API.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException(
            static::class . ' is immutable; API response objects cannot be modified.',
        );
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException(
            static::class . ' is immutable; API response objects cannot be modified.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->data;
    }

    public function __toString(): string
    {
        return static::class . ' JSON: ' . json_encode(
            $this->data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }
}
