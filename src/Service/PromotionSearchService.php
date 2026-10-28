<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Model\Promotion;
use App\Domain\Repository\CatalogRepositoryInterface;
use App\Domain\Repository\PromotionRepositoryInterface;
use App\Domain\Search\PromotionCriteria;
use App\Domain\Search\SearchResult;
use App\Support\Clock;
use App\Support\Text;

final class PromotionSearchService
{
    /** @var list<Promotion>|null */
    private ?array $active = null;
    /** @var array<string, string> promotion id => normalized searchable text */
    private array $haystacks = [];
    /** @var array<string, array<string, int>> */
    private array $offerCounts = [];

    public function __construct(
        private readonly PromotionRepositoryInterface $promotions,
        private readonly CatalogRepositoryInterface $catalog,
        private readonly LocationMatcher $locations,
        private readonly Clock $clock,
    ) {
    }

    /** @return list<Promotion> */
    public function activePromotions(): array
    {
        if ($this->active === null) {
            $now = $this->clock->now();
            $this->active = array_values(array_filter(
                $this->promotions->findAll(),
                static fn (Promotion $p): bool => $p->isActiveAt($now),
            ));
        }

        return $this->active;
    }

    public function search(PromotionCriteria $criteria): SearchResult
    {
        [$items, $storeFacets, $categoryFacets] = $this->collect($criteria);
        $items = $this->sort($items, $criteria);
        $offset = ($criteria->page - 1) * $criteria->perPage;

        return new SearchResult(
            array_slice($items, $offset, $criteria->perPage),
            count($items),
            $criteria,
            $storeFacets,
            $categoryFacets,
        );
    }

    /**
     * Every match, sorted, without pagination.
     *
     * @return list<Promotion>
     */
    public function all(PromotionCriteria $criteria): array
    {
        return $this->sort($this->collect($criteria)[0], $criteria);
    }

    /**
     * Number of stores currently promoting each product in a location.
     *
     * @return array<string, int> product key => store count
     */
    public function offerCounts(string $location, bool $includeLoyalty): array
    {
        $cacheKey = $location . ($includeLoyalty ? '+' : '-');
        if (!isset($this->offerCounts[$cacheKey])) {
            $stores = [];
            foreach ($this->activePromotions() as $p) {
                if (($includeLoyalty || !$p->loyaltyCard) && $this->locations->matches($p->zones, $location)) {
                    $stores[$p->productKey][$p->storeId] = true;
                }
            }
            $this->offerCounts[$cacheKey] = array_map('count', $stores);
        }

        return $this->offerCounts[$cacheKey];
    }

    /** @return array{list<Promotion>, array<string, int>, array<string, int>} */
    private function collect(PromotionCriteria $c): array
    {
        $tokens = Text::searchTokens($c->query);
        $items = [];
        $storeFacets = [];
        $categoryFacets = [];

        foreach ($this->activePromotions() as $p) {
            if (!$this->matchesBase($p, $c, $tokens)) {
                continue;
            }
            $inStores = $c->stores === [] || in_array($p->storeId, $c->stores, true);
            $inCategories = $c->categories === [] || in_array($p->categoryId, $c->categories, true);
            if ($inCategories) {
                $storeFacets[$p->storeId] = ($storeFacets[$p->storeId] ?? 0) + 1;
            }
            if ($inStores) {
                $categoryFacets[$p->categoryId] = ($categoryFacets[$p->categoryId] ?? 0) + 1;
            }
            if ($inStores && $inCategories) {
                $items[] = $p;
            }
        }

        return [$items, $storeFacets, $categoryFacets];
    }

    /** @param list<string> $tokens */
    private function matchesBase(Promotion $p, PromotionCriteria $c, array $tokens): bool
    {
        if ($c->productKey !== null && $p->productKey !== $c->productKey) {
            return false;
        }
        if (!$c->includeLoyalty && $p->loyaltyCard) {
            return false;
        }
        if ($p->discountPercent() < $c->minDiscount) {
            return false;
        }
        if ($c->maxPrice !== null && $p->price > $c->maxPrice) {
            return false;
        }
        if (!$this->locations->matches($p->zones, $c->location)) {
            return false;
        }
        foreach ($tokens as $token) {
            if (!str_contains($this->haystack($p), $token)) {
                return false;
            }
        }

        return true;
    }

    private function haystack(Promotion $p): string
    {
        return $this->haystacks[$p->id] ??= ' ' . Text::normalize(implode(' ', [
            $p->name,
            $p->brand,
            $p->packLabel,
            $this->catalog->category($p->categoryId)?->name ?? '',
            $this->catalog->store($p->storeId)?->name ?? '',
        ]));
    }

    /**
     * @param list<Promotion> $items
     *
     * @return list<Promotion>
     */
    private function sort(array $items, PromotionCriteria $c): array
    {
        $key = match ($c->sort) {
            'remise' => static fn (Promotion $p): array => [-$p->discountPercent(), $p->price],
            'prix' => static fn (Promotion $p): array => [$p->price, -$p->discountPercent()],
            'prix-unitaire' => static fn (Promotion $p): array => [$p->unitPrice(), $p->price],
            'fin' => static fn (Promotion $p): array => [$p->endsAt->getTimestamp(), -$p->discountPercent()],
            default => static fn (Promotion $p): array => [
                -($p->discountPercent() + (in_array($p->categoryId, $c->boostCategories, true) ? 20 : 0)),
                $p->price,
            ],
        };

        $keyed = array_map(static fn (Promotion $p): array => [$key($p), $p->id, $p], $items);
        usort($keyed, static fn (array $a, array $b): int => [$a[0], $a[1]] <=> [$b[0], $b[1]]);

        return array_column($keyed, 2);
    }
}
