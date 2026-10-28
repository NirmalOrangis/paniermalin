<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Request;
use App\Http\Response;
use App\Preferences\PreferencesResolver;
use App\Service\CriteriaFactory;
use App\Service\PromotionSearchService;
use App\View\View;

/** The promotions explorer: filters, sort and paginated results. */
final class PromotionController
{
    public function __construct(
        private readonly View $view,
        private readonly PreferencesResolver $preferences,
        private readonly CriteriaFactory $criteria,
        private readonly PromotionSearchService $search,
    ) {
    }

    public function index(Request $request): Response
    {
        $prefs = $this->preferences->fromRequest($request);
        $criteria = $this->criteria->fromRequest($request, $prefs);
        $h = $this->view->helpers($request, $prefs, $criteria->location);

        $title = 'Toutes les promos';
        if ($criteria->query !== '') {
            $title = 'Promos « ' . $criteria->query . ' »';
        } elseif (count($criteria->categories) === 1) {
            $title = 'Promos ' . ($h->category($criteria->categories[0])?->name ?? '');
        }

        return $this->view->page('promos', [
            'h' => $h,
            'prefs' => $prefs,
            'criteria' => $criteria,
            'pageId' => 'promos',
            'title' => $title,
            'query' => $criteria->query,
            'result' => $this->search->search($criteria),
            'offerCounts' => $this->search->offerCounts($criteria->location, $criteria->includeLoyalty),
        ]);
    }
}
