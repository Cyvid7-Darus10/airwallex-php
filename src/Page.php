<?php

declare(strict_types=1);

namespace Airwallex;

/**
 * One page of results with lazy access to the following pages.
 *
 * Iterate the page itself for just its items, or use
 * {@see autoPagingIterator()} to transparently walk every page:
 *
 *     $page = $client->beneficiaries->list();
 *     foreach ($page->autoPagingIterator() as $beneficiary) {
 *         // ...
 *     }
 *
 * @template T of AirwallexObject
 *
 * @implements \IteratorAggregate<int, T>
 */
final class Page implements \IteratorAggregate, \Countable
{
    /**
     * The items on this page.
     *
     * @var list<T>
     */
    public readonly array $items;

    /**
     * Whether more pages follow this one.
     */
    public readonly bool $hasMore;

    /**
     * @param \Closure(int): mixed $fetch fetches the raw payload for a page number
     * @param class-string<T> $itemClass
     */
    public function __construct(
        private readonly \Closure $fetch,
        private readonly int $pageNum,
        mixed $data,
        private readonly string $itemClass,
    ) {
        $payload = \is_array($data) ? $data : [];
        $rawItems = $payload['items'] ?? [];
        $items = [];
        if (\is_array($rawItems)) {
            foreach ($rawItems as $item) {
                $items[] = $this->itemClass::make($item);
            }
        }
        $this->items = $items;
        $this->hasMore = (bool) ($payload['has_more'] ?? false);
    }

    /**
     * @return \ArrayIterator<int, T>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    public function count(): int
    {
        return \count($this->items);
    }

    /**
     * Fetch the page after this one. Check {@see $hasMore} first.
     *
     * @return self<T>
     */
    public function nextPage(): self
    {
        $next = $this->pageNum + 1;

        return new self($this->fetch, $next, ($this->fetch)($next), $this->itemClass);
    }

    /**
     * Yield every item across this page and all following pages.
     *
     * Iteration stops when the API reports no more pages — or defensively
     * when a page claims has_more but carries no items, so a misbehaving
     * response can never loop forever.
     *
     * @return \Generator<int, T>
     */
    public function autoPagingIterator(): \Generator
    {
        $page = $this;
        while (true) {
            // Yield without explicit keys so indexes stay sequential across
            // pages (yield from would restart keys at 0 for every page).
            foreach ($page->items as $item) {
                yield $item;
            }
            if (!$page->hasMore || $page->items === []) {
                return;
            }
            $page = $page->nextPage();
        }
    }
}
