<?php

declare(strict_types=1);

namespace Airwallex\Service;

use Airwallex\Page;
use Airwallex\Resource\WebhookEndpoint;
use Airwallex\Util;

/**
 * Manage webhook subscriptions (where Airwallex sends event notifications).
 */
final class WebhookEndpointsService extends AbstractService
{
    private const BASE = '/api/v1/webhooks';

    /**
     * List registered webhook endpoints.
     *
     * @param array<string, mixed> $extraParams
     *
     * @return Page<WebhookEndpoint>
     */
    public function list(
        int $pageNum = 0,
        ?int $pageSize = null,
        array $extraParams = [],
    ): Page {
        return $this->paged(self::BASE, WebhookEndpoint::class, array_merge([
            'page_num' => $pageNum,
            'page_size' => $pageSize,
        ], $extraParams));
    }

    /**
     * Fetch a single webhook endpoint by id.
     */
    public function retrieve(string $webhookId): WebhookEndpoint
    {
        return WebhookEndpoint::make(
            $this->client->get(self::BASE . '/' . Util::encodePathParam($webhookId)),
        );
    }

    /**
     * Register a webhook endpoint for the given event names.
     *
     * $version pins the API version that controls the event payload
     * structure (YYYY-MM-DD); the API requires it.
     *
     * @param list<string> $events
     * @param array<string, mixed> $params
     */
    public function create(string $url, array $events, ?string $version = null, array $params = []): WebhookEndpoint
    {
        return WebhookEndpoint::make($this->client->post(
            self::BASE . '/create',
            Util::ensureRequestId(array_merge(
                Util::cleanParams(['url' => $url, 'events' => $events, 'version' => $version]),
                $params,
            )),
        ));
    }

    /**
     * Update a webhook endpoint's url or subscribed events.
     *
     * @param array<string, mixed> $params
     */
    public function update(string $webhookId, array $params): WebhookEndpoint
    {
        return WebhookEndpoint::make($this->client->post(
            self::BASE . '/' . Util::encodePathParam($webhookId) . '/update',
            $params,
        ));
    }

    /**
     * Delete a webhook endpoint.
     */
    public function delete(string $webhookId): void
    {
        $this->client->post(self::BASE . '/' . Util::encodePathParam($webhookId) . '/delete');
    }
}
