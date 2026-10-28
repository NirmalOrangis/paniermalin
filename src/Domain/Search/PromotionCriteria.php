<?php

declare(strict_types=1);

namespace App\Domain\Search;

/** Immutable search filters; derive variants with with(). */
final class PromotionCriteria
{
    public const DEFAULT_SORT = 'pertinence';
    public const SORTS = [
        'pertinence' => 'Pertinence',
        'remise' => 'Plus forte remise',
        'prix' => 'Prix le plus bas',
        'prix-unitaire' => 'Prix au kg / L le plus bas',
        'fin' => 'Se termine bientôt',
    ];

    /**
     * @param list<string> $stores          empty = all stores
     * @param list<string> $categories      empty = all categories
     * @param list<string> $boostCategories user interests, ranked higher with the "pertinence" sort
     */
    public function __construct(
        public readonly string $location,
        public readonly array $stores = [],
        public readonly array $categories = [],
        public readonly string $query = '',
        public readonly string $sort = self::DEFAULT_SORT,
        public readonly bool $includeLoyalty = true,
        public readonly int $minDiscount = 0,
        public readonly ?float $maxPrice = null,
        public readonly int $page = 1,
        public readonly int $perPage = 24,
        public readonly array $boostCategories = [],
        public readonly ?string $productKey = null,
    ) {
    }

    /** @param array<string, mixed> $changes constructor argument names => new values */
    public function with(array $changes): self
    {
        return new self(...array_merge(get_object_vars($this), $changes));
    }

    /**
     * Query-string parameters reproducing these filters on /promos ("f=1"
     * tells the server the filters are explicit and must not fall back to
     * the saved preferences).
     *
     * @return array<string, string|int>
     */
    public function toQuery(): array
    {
        return array_filter([
            'f' => '1',
            'q' => $this->query !== '' ? $this->query : null,
            'stores' => implode(',', $this->stores),
            'cat' => $this->categories ? implode(',', $this->categories) : null,
            'sort' => $this->sort !== self::DEFAULT_SORT ? $this->sort : null,
            'loyal' => $this->includeLoyalty ? '1' : null,
            'remise' => $this->minDiscount ?: null,
            'prixmax' => $this->maxPrice,
            'page' => $this->page > 1 ? $this->page : null,
        ], static fn ($v): bool => $v !== null);
    }
}
