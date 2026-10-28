<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Model\Promotion;
use App\Http\Request;
use App\Http\Response;
use App\Preferences\PreferencesResolver;
use App\Service\BasketOptimizer;
use App\Service\CriteriaFactory;
use App\Service\PriceComparisonService;
use App\Service\PromotionSearchService;
use App\Support\Text;
use App\View\View;

/**
 * JSON endpoints used by the front-end. Each response carries server-rendered
 * HTML ("html") so cards look identical with or without JavaScript, plus raw
 * data for other clients (a future mobile app, for instance).
 */
final class ApiController
{
    private const MAX_LIST_ITEMS = 60;

    public function __construct(
        private readonly View $view,
        private readonly PreferencesResolver $preferences,
        private readonly CriteriaFactory $criteria,
        private readonly PromotionSearchService $search,
        private readonly PriceComparisonService $comparison,
        private readonly BasketOptimizer $basket,
    ) {
    }

    public function promotions(Request $request): Response
    {
        $prefs = $this->preferences->fromRequest($request);
        $criteria = $this->criteria->fromRequest($request, $prefs);
        $result = $this->search->search($criteria);
        $h = $this->view->helpers($request, $prefs, $criteria->location);

        return Response::json([
            'total' => $result->total,
            'totalLabel' => $h->plural($result->total, 'promotion', 'promotions'),
            'page' => $criteria->page,
            'hasMore' => $result->hasMore(),
            'nextUrl' => $h->url('/promos', $criteria->with(['page' => $criteria->page + 1])->toQuery()),
            'facets' => ['stores' => $result->storeFacets, 'categories' => $result->categoryFacets],
            'html' => $this->view->render('partials/cards', [
                'h' => $h,
                'items' => $result->items,
                'offerCounts' => $this->search->offerCounts($criteria->location, $criteria->includeLoyalty),
            ]),
            'items' => array_map(static fn (Promotion $p): array => $p->toArray(), $result->items),
        ])->withEtag($request);
    }

    public function suggest(Request $request): Response
    {
        $prefs = $this->preferences->fromRequest($request);
        $criteria = $this->criteria->fromRequest($request, $prefs)->with(['stores' => [], 'categories' => []]);
        $h = $this->view->helpers($request, $prefs, $criteria->location);

        $query = $criteria->query;
        $products = mb_strlen($query) >= 2 ? $this->comparison->find($criteria, 6) : [];
        $needle = Text::normalize($query);
        $categories = $needle === '' ? [] : array_values(array_filter(
            $h->categories(),
            static fn ($c): bool => str_contains(Text::normalize($c->name), $needle),
        ));

        return Response::json([
            'html' => $this->view->render('partials/suggest', ['h' => $h, 'query' => $query, 'products' => $products, 'categories' => $categories]),
            'count' => count($products) + count($categories),
        ])->withEtag($request);
    }

    public function basket(Request $request): Response
    {
        $prefs = $this->preferences->fromRequest($request);
        $criteria = $this->criteria->fromRequest($request, $prefs)->with(['stores' => []]);
        $h = $this->view->helpers($request, $prefs, $criteria->location);

        $keys = array_slice(
            array_values(array_filter($request->queryList('items'), static fn (string $k): bool => (bool) preg_match('/^[a-z0-9-]{1,80}$/', $k))),
            0,
            self::MAX_LIST_ITEMS,
        );
        $plan = $this->basket->plan($keys, $criteria);

        return Response::json([
            'html' => $this->view->render('partials/basket', ['h' => $h, 'plan' => $plan]),
            'bestMixTotal' => $plan->bestMixTotal,
            'bestSingleStore' => $plan->bestSingleStore() === null ? null : [
                'store' => $plan->bestSingleStore()['store'],
                'total' => $plan->bestSingleStore()['total'],
                'found' => $plan->bestSingleStore()['found'],
            ],
            'missing' => $plan->missing(),
        ])->withEtag($request);
    }
}
