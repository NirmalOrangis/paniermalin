<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Model\ProductOffers;
use App\Http\Request;
use App\Http\Response;
use App\Preferences\PreferencesResolver;
use App\Service\CriteriaFactory;
use App\Service\PriceComparisonService;
use App\View\View;

/** Same product, every store: who is cheapest right now. */
final class CompareController
{
    public function __construct(
        private readonly View $view,
        private readonly PreferencesResolver $preferences,
        private readonly CriteriaFactory $criteria,
        private readonly PriceComparisonService $comparison,
    ) {
    }

    public function index(Request $request): Response
    {
        $prefs = $this->preferences->fromRequest($request);
        $criteria = $this->criteria->fromRequest($request, $prefs)->with(['stores' => [], 'categories' => []]);
        $h = $this->view->helpers($request, $prefs, $criteria->location);

        $products = $criteria->query !== '' ? $this->comparison->find($criteria) : [];
        if (count($products) === 1) {
            return Response::redirect($h->url('/comparer/' . $products[0]->productKey));
        }

        return $this->view->page('compare', [
            'h' => $h,
            'prefs' => $prefs,
            'criteria' => $criteria,
            'pageId' => 'compare',
            'title' => 'Comparateur de prix',
            'query' => $criteria->query,
            'products' => $products,
            'gaps' => $criteria->query === '' ? $this->comparison->biggestGaps($criteria->with(['query' => '']), 12) : [],
        ]);
    }

    public function show(Request $request, string $key): Response
    {
        $prefs = $this->preferences->fromRequest($request);
        $criteria = $this->criteria->fromRequest($request, $prefs);
        $h = $this->view->helpers($request, $prefs, $criteria->location);

        $offers = $this->comparison->forProduct($key, $criteria);
        $elsewhere = $offers === null ? $this->comparison->forProduct($key, $criteria->with(['location' => 'FR'])) : null;
        $reference = ($offers ?? $elsewhere)?->cheapest();
        if ($reference === null) {
            return $this->view->page('error', ['h' => $h, 'prefs' => $prefs, 'title' => 'Produit introuvable', 'status' => 404], 404);
        }

        $similar = array_values(array_filter(
            $this->comparison->biggestGaps($criteria->with(['stores' => [], 'categories' => [$reference->categoryId], 'query' => '']), 5),
            static fn (ProductOffers $g): bool => $g->productKey !== $key,
        ));

        return $this->view->page('product', [
            'h' => $h,
            'prefs' => $prefs,
            'criteria' => $criteria,
            'pageId' => 'compare',
            'title' => trim($reference->brand . ' ' . $reference->name . ' ' . $reference->packLabel) . ' : comparer les prix',
            'description' => sprintf('Comparez le prix de %s %s dans les supermarchés : promos en cours chez Carrefour, Auchan, Leclerc, Lidl…', $reference->brand, $reference->name),
            'reference' => $reference,
            'offers' => $offers,
            'elsewhere' => $elsewhere,
            'similar' => array_slice($similar, 0, 4),
        ]);
    }
}
