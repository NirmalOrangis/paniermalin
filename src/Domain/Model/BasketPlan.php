<?php

declare(strict_types=1);

namespace App\Domain\Model;

/** Result of optimising a shopping list across stores. */
final class BasketPlan
{
    /**
     * @param array<string, ?ProductOffers> $products   product key => offers (null when no current promo)
     * @param list<array{store: string, total: float, found: int, items: array<string, Promotion>}> $stores
     *        one entry per store, best coverage first then cheapest
     * @param array<string, list<Promotion>> $bestMix    store id => cheapest offers to buy there
     */
    public function __construct(
        public readonly array $products,
        public readonly array $stores,
        public readonly array $bestMix,
        public readonly float $bestMixTotal,
    ) {
    }

    public function availableCount(): int
    {
        return count(array_filter($this->products));
    }

    /** @return list<string> */
    public function missing(): array
    {
        // array keys holding barcodes come back as integers
        return array_map('strval', array_keys(array_filter($this->products, static fn (?ProductOffers $o): bool => $o === null)));
    }

    /** @return array{store: string, total: float, found: int, items: array<string, Promotion>}|null */
    public function bestSingleStore(): ?array
    {
        return $this->stores[0] ?? null;
    }
}
