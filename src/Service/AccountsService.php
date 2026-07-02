<?php

declare(strict_types=1);

namespace Airwallex\Service;

use Airwallex\Resource\Account;

/**
 * Details of the Airwallex account the API credentials belong to.
 */
final class AccountsService extends AbstractService
{
    private const BASE = '/api/v1/account';

    /**
     * Fetch your own account details.
     */
    public function retrieve(): Account
    {
        return Account::make($this->client->get(self::BASE));
    }
}
