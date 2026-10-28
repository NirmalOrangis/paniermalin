<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Model\BasketPlan;
use App\Domain\Model\ProductOffers;
use App\Domain\Search\PromotionCriteria;

/**
 * For a shopping list, finds the cheapest single store and the cheapest
 * combination of stores among the current promotions.
 */
final class BasketOptimizer
{
    public function __construct(private readonly PriceComparisonService $comparison)
    {
    }

    /** @param list<string> $productKeys */
    public function plan(array $productKeys, PromotionCriteria $base): BasketPlan
    {
        $products = [];
        foreach ($productKeys as $key) {
            $products[$key] = $this->comparison->forProduct($key, $base);
        }

        $stores = [];
        $bestMix = [];
        $bestMixTotal = 0.0;
        foreach ($products as $key => $offers) {
            if (!$offers instanceof ProductOffers) {
                continue;
            }
            foreach ($offers->offers as $offer) {
                $stores[$offer->storeId] ??= ['store' => $offer->storeId, 'total' => 0.0, 'found' => 0, 'items' => []];
                $stores[$offer->storeId]['total'] += $offer->price;
                $stores[$offer->storeId]['found']++;
                $stores[$offer->storeId]['items'][$key] = $offer;
            }
            $cheapest = $offers->cheapest();
            $bestMix[$cheapest->storeId][] = $cheapest;
            $bestMixTotal += $cheapest->price;
        }

        $stores = array_values($stores);
        usort($stores, static fn (array $a, array $b): int => [$b['found'], $a['total']] <=> [$a['found'], $b['total']]);
        foreach ($stores as &$store) {
            $store['total'] = round($store['total'], 2);
        }
        unset($store);

        return new BasketPlan($products, $stores, $bestMix, round($bestMixTotal, 2));
    }
}
