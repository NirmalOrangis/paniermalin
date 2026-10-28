<?php

declare(strict_types=1);

namespace App\Domain\Search;

use App\Domain\Model\Promotion;

final class SearchResult
{
    /**
     * @param list<Promotion>     $items          current page
     * @param array<string, int>  $storeFacets    store id => matches, ignoring the store filter
     * @param array<string, int>  $categoryFacets category id => matches, ignoring the category filter
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly PromotionCriteria $criteria,
        public readonly array $storeFacets,
        public readonly array $categoryFacets,
    ) {
    }

    public function hasMore(): bool
    {
        return $this->criteria->page * $this->criteria->perPage < $this->total;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }
}
