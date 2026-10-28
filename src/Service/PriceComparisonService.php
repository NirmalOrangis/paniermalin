<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Model\ProductOffers;
use App\Domain\Model\Promotion;
use App\Domain\Search\PromotionCriteria;

/** Groups offers by product to compare the same item across stores. */
final class PriceComparisonService
{
    public function __construct(private readonly PromotionSearchService $search)
    {
    }

    public function forProduct(string $productKey, PromotionCriteria $base): ?ProductOffers
    {
        $criteria = $base->with(['productKey' => $productKey, 'stores' => [], 'categories' => [], 'query' => '', 'minDiscount' => 0, 'maxPrice' => null]);

        return $this->group($this->search->all($criteria))[$productKey] ?? null;
    }

    /**
     * Products matching the criteria, most widely available first.
     *
     * @return list<ProductOffers>
     */
    public function find(PromotionCriteria $criteria, int $limit = 30): array
    {
        $groups = array_values($this->group($this->search->all($criteria)));
        usort($groups, static fn (ProductOffers $a, ProductOffers $b): int => [$b->storeCount(), $a->cheapest()->name] <=> [$a->storeCount(), $b->cheapest()->name]);

        return array_slice($groups, 0, $limit);
    }

    /**
     * Products whose price differs the most between stores: where comparing pays off.
     *
     * @return list<ProductOffers>
     */
    public function biggestGaps(PromotionCriteria $criteria, int $limit): array
    {
        $groups = array_filter($this->group($this->search->all($criteria)), static fn (ProductOffers $g): bool => $g->storeCount() >= 2);
        usort($groups, static fn (ProductOffers $a, ProductOffers $b): int => [$b->spreadPercent(), $b->spread()] <=> [$a->spreadPercent(), $a->spread()]);

        return array_slice($groups, 0, $limit);
    }

    /**
     * @param list<Promotion> $promotions
     *
     * @return array<string, ProductOffers>
     */
    private function group(array $promotions): array
    {
        $byProduct = [];
        foreach ($promotions as $p) {
            $current = $byProduct[$p->productKey][$p->storeId] ?? null;
            if ($current === null || $p->price < $current->price) {
                $byProduct[$p->productKey][$p->storeId] = $p;
            }
        }

        $groups = [];
        foreach ($byProduct as $key => $offers) {
            $offers = array_values($offers);
            usort($offers, static fn (Promotion $a, Promotion $b): int => $a->price <=> $b->price);
            // PHP turns numeric keys such as barcodes into integers.
            $groups[$key] = new ProductOffers((string) $key, $offers);
        }

        return $groups;
    }
}
