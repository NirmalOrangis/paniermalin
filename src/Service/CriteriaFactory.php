<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Repository\CatalogRepositoryInterface;
use App\Domain\Search\PromotionCriteria;
use App\Http\Request;
use App\Preferences\UserPreferences;

/**
 * Builds search criteria from the query string, falling back to the
 * visitor's saved preferences for anything not given explicitly.
 */
final class CriteriaFactory
{
    public const DISCOUNT_STEPS = [0, 20, 30, 40, 50];

    public function __construct(
        private readonly CatalogRepositoryInterface $catalog,
        private readonly string $defaultLocation,
        private readonly int $perPage,
    ) {
    }

    public function fromRequest(Request $request, UserPreferences $prefs): PromotionCriteria
    {
        // "f=1" is sent by the filter form: its values are explicit, even when empty.
        $explicit = $request->query('f') === '1';

        $location = $request->query('loc');
        if ($this->catalog->location($location) === null) {
            $location = $prefs->location ?? $this->defaultLocation;
        }

        // Saved favourite stores apply to browsing, not to a fresh text search:
        // someone typing "café" wants every store's offers.
        if ($explicit || $request->has('stores')) {
            $stores = array_values(array_filter($request->queryList('stores'), fn (string $id): bool => $this->catalog->store($id) !== null));
        } else {
            $stores = $request->query('q') === '' ? $prefs->stores : [];
        }

        if ($explicit) {
            $includeLoyalty = $request->query('loyal') === '1';
        } elseif ($request->has('loyal')) {
            $includeLoyalty = $request->query('loyal') !== '0';
        } else {
            $includeLoyalty = $prefs->includeLoyalty;
        }

        $sort = $request->query('sort');
        $minDiscount = (int) $request->query('remise', '0');
        $maxPrice = (float) str_replace(',', '.', $request->query('prixmax', '0'));

        return new PromotionCriteria(
            location: $location,
            stores: $stores,
            categories: array_values(array_filter($request->queryList('cat'), fn (string $id): bool => $this->catalog->category($id) !== null)),
            query: mb_substr($request->query('q'), 0, 80),
            sort: array_key_exists($sort, PromotionCriteria::SORTS) ? $sort : PromotionCriteria::DEFAULT_SORT,
            includeLoyalty: $includeLoyalty,
            minDiscount: in_array($minDiscount, self::DISCOUNT_STEPS, true) ? $minDiscount : 0,
            maxPrice: $maxPrice > 0 ? round($maxPrice, 2) : null,
            page: max(1, min(500, $request->queryInt('page', 1))),
            perPage: max(1, min(60, $request->queryInt('limit', $this->perPage))),
            boostCategories: $prefs->topInterests(3),
        );
    }
}
