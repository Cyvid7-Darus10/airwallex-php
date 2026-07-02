<?php

declare(strict_types=1);

namespace Airwallex\Service;

use Airwallex\Resource\RateQuote;
use Airwallex\Util;

/**
 * Indicative FX rates (no funds move).
 */
final class RatesService extends AbstractService
{
    private const RATES_CURRENT = '/api/v1/fx/rates/current';

    /**
     * Get the current indicative FX rate (no funds move).
     *
     * Specify at most one of $buyAmount / $sellAmount; Airwallex defaults to
     * a notional amount of 10,000 when neither is given. For a bookable rate
     * use {@see FxQuotesService::create()} instead.
     */
    public function current(
        string $buyCurrency,
        string $sellCurrency,
        float|int|null $buyAmount = null,
        float|int|null $sellAmount = null,
        ?string $conversionDate = null,
    ): RateQuote {
        return RateQuote::make($this->client->get(self::RATES_CURRENT, Util::cleanParams([
            'buy_currency' => $buyCurrency,
            'sell_currency' => $sellCurrency,
            'buy_amount' => $buyAmount,
            'sell_amount' => $sellAmount,
            'conversion_date' => $conversionDate,
        ])));
    }
}
