<?php

declare(strict_types=1);

namespace Airwallex\Tests;

use Airwallex\Resource\Balance;
use Airwallex\Resource\Beneficiary;

final class PaginationTest extends TestCase
{
    public function testAutoPagingIteratesEveryPage(): void
    {
        $client = $this->client([
            self::loginResponse(),
            self::page([['beneficiary_id' => 'ben_1'], ['beneficiary_id' => 'ben_2']], hasMore: true),
            self::page([['beneficiary_id' => 'ben_3']], hasMore: true),
            self::page([['beneficiary_id' => 'ben_4']], hasMore: false),
        ]);

        $ids = [];
        foreach ($client->beneficiaries->list()->autoPagingIterator() as $beneficiary) {
            self::assertInstanceOf(Beneficiary::class, $beneficiary);
            $ids[] = $beneficiary->beneficiary_id;
        }

        self::assertSame(['ben_1', 'ben_2', 'ben_3', 'ben_4'], $ids);

        // page_num must advance 0 -> 1 -> 2.
        $pageNums = array_map(
            static fn ($request) => $request->getUri()->getQuery(),
            $this->dataRequests(),
        );
        self::assertSame(['page_num=0', 'page_num=1', 'page_num=2'], $pageNums);
    }

    public function testIteratorKeysStaySequentialAcrossPages(): void
    {
        $client = $this->client([
            self::loginResponse(),
            self::page([['beneficiary_id' => 'ben_1']], hasMore: true),
            self::page([['beneficiary_id' => 'ben_2']], hasMore: false),
        ]);

        $items = iterator_to_array($client->beneficiaries->list()->autoPagingIterator());

        self::assertCount(2, $items);
    }

    public function testListStartsAtTheRequestedOffset(): void
    {
        $client = $this->client([
            self::loginResponse(),
            self::page([['beneficiary_id' => 'ben_20']], hasMore: false),
        ]);

        $page = $client->beneficiaries->list(pageNum: 2, pageSize: 10);

        self::assertCount(1, $page);
        self::assertSame('page_size=10&page_num=2', $this->dataRequests()[0]->getUri()->getQuery());
    }

    public function testManualNextPage(): void
    {
        $client = $this->client([
            self::loginResponse(),
            self::page([['beneficiary_id' => 'ben_1']], hasMore: true),
            self::page([['beneficiary_id' => 'ben_2']], hasMore: false),
        ]);

        $page = $client->beneficiaries->list();
        self::assertTrue($page->hasMore);
        self::assertSame('ben_1', $page->items[0]->beneficiary_id);

        $next = $page->nextPage();
        self::assertFalse($next->hasMore);
        self::assertSame('ben_2', $next->items[0]->beneficiary_id);
    }

    public function testHasMoreWithEmptyItemsTerminatesIteration(): void
    {
        // A misbehaving response claims more pages but returns nothing; the
        // iterator must stop rather than loop forever.
        $client = $this->client([
            self::loginResponse(),
            self::page([['beneficiary_id' => 'ben_1']], hasMore: true),
            self::page([], hasMore: true),
        ]);

        $ids = [];
        foreach ($client->beneficiaries->list()->autoPagingIterator() as $beneficiary) {
            $ids[] = $beneficiary->beneficiary_id;
        }

        self::assertSame(['ben_1'], $ids);
        self::assertCount(2, $this->dataRequests());
    }

    public function testPageIsCountableAndIterable(): void
    {
        $client = $this->client([
            self::loginResponse(),
            self::page([['beneficiary_id' => 'ben_1'], ['beneficiary_id' => 'ben_2']]),
        ]);

        $page = $client->beneficiaries->list();

        self::assertCount(2, $page);
        self::assertCount(2, iterator_to_array($page));
        self::assertFalse($page->hasMore);
    }

    public function testFilterParamsPropagateToEveryPage(): void
    {
        $client = $this->client([
            self::loginResponse(),
            self::page([['id' => 't_1']], hasMore: true),
            self::page([['id' => 't_2']]),
        ]);

        iterator_to_array($client->transfers->list(status: 'PAID', extraParams: ['funding_source' => 'x'])->autoPagingIterator());

        foreach ($this->dataRequests() as $request) {
            parse_str($request->getUri()->getQuery(), $query);
            self::assertSame('PAID', $query['status']);
            self::assertSame('x', $query['funding_source']);
        }
    }

    public function testMissingItemsKeyYieldsAnEmptyPage(): void
    {
        $client = $this->client([self::loginResponse(), self::json(200, ['has_more' => false])]);

        $page = $client->beneficiaries->list();

        self::assertCount(0, $page);
        self::assertFalse($page->hasMore);
        self::assertSame([], iterator_to_array($page->autoPagingIterator()));
    }

    public function testBalancesCurrentReturnsAListOfTypedObjects(): void
    {
        $client = $this->client([
            self::loginResponse(),
            self::json(200, [
                ['currency' => 'USD', 'available_amount' => 100.5],
                ['currency' => 'SGD', 'available_amount' => 20],
            ]),
        ]);

        $balances = $client->balances->current();

        self::assertCount(2, $balances);
        self::assertContainsOnlyInstancesOf(Balance::class, $balances);
        self::assertSame('USD', $balances[0]->currency);
        self::assertSame(100.5, $balances[0]->available_amount);
    }
}
