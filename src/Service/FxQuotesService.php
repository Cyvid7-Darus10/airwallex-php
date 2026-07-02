<?php

declare(strict_types=1);

namespace Airwallex\Service;

use Airwallex\Resource\FxQuote;
use Airwallex\Util;

/**
 * Lockable FX quotes (/api/v1/fx/quotes).
 *
 * A quote locks a rate for a currency pair until it expires; pass its id
 * when executing a conversion to trade at exactly the quoted rate. For a
 * purely indicative rate, use $client->rates->current() instead.
 */
final class FxQuotesService extends AbstractService
{
    private const BASE = '/api/v1/fx/quotes';

    /**
     * Create a lockable FX quote.
     *
     * A request_id is generated automatically when not supplied, making the
     * call idempotent — Airwallex will never execute the same request_id
     * twice, even across the SDK's automatic retries.
     *
     *     $client->fxQuotes->create([
     *         'buy_currency' => 'USD',
     *         'sell_currency' => 'SGD',
     *         'buy_amount' => 1000,
     *         'validity' => 'HR_1',
     *     ]);
     *
     * @param array<string, mixed> $params
     */
    public function create(array $params): FxQuote
    {
        return FxQuote::make(
            $this->client->post(self::BASE . '/create', Util::ensureRequestId($params)),
        );
    }

    /**
     * Fetch a single FX quote by id.
     */
    public function retrieve(string $quoteId): FxQuote
    {
        return FxQuote::make(
            $this->client->get(self::BASE . '/' . Util::encodePathParam($quoteId)),
        );
    }
}
