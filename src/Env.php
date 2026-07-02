<?php

declare(strict_types=1);

namespace Airwallex;

/**
 * Airwallex API environment.
 */
enum Env: string
{
    case Production = 'production';
    case Demo = 'demo';

    /**
     * The API host for this environment.
     */
    public function baseUrl(): string
    {
        return match ($this) {
            self::Production => 'https://api.airwallex.com',
            self::Demo => 'https://api-demo.airwallex.com',
        };
    }
}
