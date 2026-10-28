<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Request;
use App\Http\Response;
use App\Preferences\PreferencesResolver;
use App\Service\CriteriaFactory;
use App\Service\PriceComparisonService;
use App\Service\PromotionSearchService;
use App\View\View;

final class HomeController
{
    private const SECTION_SIZE = 8;

    public function __construct(
        private readonly View $view,
        private readonly PreferencesResolver $preferences,
        private readonly CriteriaFactory $criteria,
        private readonly PromotionSearchService $search,
        private readonly PriceComparisonService $comparison,
    ) {
    }

    public function index(Request $request): Response
    {
        $prefs = $this->preferences->fromRequest($request);
        // The home page only honours the location and the saved preferences.
        $base = $this->criteria->fromRequest($request, $prefs)
            ->with(['categories' => [], 'query' => '', 'page' => 1, 'perPage' => self::SECTION_SIZE, 'minDiscount' => 0, 'maxPrice' => null]);
        $allStores = $base->with(['stores' => []]);

        $overview = $this->search->search($allStores->with(['perPage' => 1]));
        $interests = $prefs->topInterests(3);
        $otherCategories = array_values(array_diff(array_keys($overview->categoryFacets), $interests));

        return $this->view->page('home', [
            'h' => $this->view->helpers($request, $prefs, $base->location),
            'prefs' => $prefs,
            'criteria' => $base,
            'pageId' => 'home',
            'overview' => $overview,
            'forYou' => $interests ? $this->search->search($base->with(['categories' => $interests])) : null,
            'topDeals' => $this->search->search($base->with(['sort' => 'remise'])),
            'gaps' => $this->comparison->biggestGaps($allStores, 6),
            'endingSoon' => $this->search->search($base->with(['sort' => 'fin'])),
            'discover' => $interests && $otherCategories ? $this->search->search($allStores->with(['categories' => $otherCategories, 'sort' => 'remise'])) : null,
            'offerCounts' => $this->search->offerCounts($base->location, $base->includeLoyalty),
        ]);
    }
}
