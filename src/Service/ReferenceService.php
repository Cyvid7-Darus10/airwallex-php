<?php

declare(strict_types=1);

namespace Airwallex\Service;

use Airwallex\Util;

/**
 * Static reference data (currencies, settlement accounts, FX calendar).
 */
final class ReferenceService extends AbstractService
{
    private const BASE = '/api/v1/reference';

    /**
     * List currencies supported for collection and payout.
     */
    public function supportedCurrencies(): mixed
    {
        return $this->client->get(self::BASE . '/supported_currencies');
    }

    /**
     * List settlement accounts available for the given corridor.
     */
    public function settlementAccounts(
        ?string $countryCode = null,
        ?string $currency = null,
    ): mixed {
        return $this->client->get(self::BASE . '/settlement_accounts', Util::cleanParams([
            'country_code' => $countryCode,
            'currency' => $currency,
        ]));
    }

    /**
     * List dates on which the given currency pair cannot settle.
     */
    public function invalidConversionDates(string $currencyPair): mixed
    {
        return $this->client->get(self::BASE . '/invalid_conversion_dates', [
            'currency_pair' => $currencyPair,
        ]);
    }
}
