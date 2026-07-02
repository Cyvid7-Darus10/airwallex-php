<?php

declare(strict_types=1);

namespace Airwallex\Service;

use Airwallex\AirwallexObject;
use Airwallex\ApiClient;
use Airwallex\Page;
use Airwallex\Util;

/**
 * Base class for all API services.
 *
 * @internal Extend via the SDK only; the service surface is the public API.
 */
abstract class AbstractService
{
    public function __construct(protected readonly ApiClient $client)
    {
    }

    /**
     * Fetch the first page of a list endpoint and wire up lazy pagination.
     *
     * @template T of AirwallexObject
     *
     * @param class-string<T> $itemClass
     * @param array<string, mixed> $params
     *
     * @return Page<T>
     */
    protected function paged(string $path, string $itemClass, array $params = []): Page
    {
        $query = Util::cleanParams($params);
        $rawPageNum = $query['page_num'] ?? 0;
        $pageNum = is_numeric($rawPageNum) ? (int) $rawPageNum : 0;
        unset($query['page_num']);

        $fetch = fn (int $num): mixed => $this->client->get(
            $path,
            array_merge($query, ['page_num' => $num]),
        );

        return new Page($fetch, $pageNum, $fetch($pageNum), $itemClass);
    }
}
