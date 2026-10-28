<?php

declare(strict_types=1);

namespace App\Domain\Model;

/** All current offers for one product, cheapest first, at most one per store. */
final class ProductOffers
{
    /** @param non-empty-list<Promotion> $offers */
    public function __construct(
        public readonly string $productKey,
        public readonly array $offers,
    ) {
    }

    public function cheapest(): Promotion
    {
        return $this->offers[0];
    }

    public function priciest(): Promotion
    {
        return $this->offers[count($this->offers) - 1];
    }

    public function storeCount(): int
    {
        return count($this->offers);
    }

    /** Euros saved by buying at the cheapest store rather than the priciest one. */
    public function spread(): float
    {
        return round($this->priciest()->price - $this->cheapest()->price, 2);
    }

    public function spreadPercent(): int
    {
        $max = $this->priciest()->price;

        return $max > 0 ? (int) round($this->spread() / $max * 100) : 0;
    }
}
