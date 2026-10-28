<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Request;
use App\Http\Response;
use App\Preferences\PreferencesResolver;
use App\Preferences\UserPreferences;
use App\Service\CriteriaFactory;
use App\Service\PromotionSearchService;
use App\View\View;

final class PageController
{
    public function __construct(
        private readonly View $view,
        private readonly PreferencesResolver $preferences,
        private readonly CriteriaFactory $criteria,
        private readonly PromotionSearchService $search,
    ) {
    }

    /** Shopping list: the list lives in the browser, app.js fetches the plan from /api/basket. */
    public function shoppingList(Request $request): Response
    {
        return $this->simplePage($request, 'list', 'Ma liste de courses', 'list');
    }

    public function privacy(Request $request): Response
    {
        return $this->simplePage($request, 'privacy', 'Confidentialité et cookies', 'privacy');
    }

    public function notFound(Request $request): Response
    {
        $prefs = $this->preferences->fromRequest($request);
        $criteria = $this->criteria->fromRequest($request, $prefs);

        return $this->view->page('error', [
            'h' => $this->view->helpers($request, $prefs, $criteria->location),
            'prefs' => $prefs,
            'title' => 'Page introuvable',
            'status' => 404,
        ], 404);
    }

    public function sitemap(Request $request): Response
    {
        // Public URLs: never carry a visitor's location.
        $h = $this->view->helpers($request, new UserPreferences(true), 'FR');
        $products = array_unique(array_map(static fn ($p): string => $p->productKey, $this->search->activePromotions()));

        return Response::xml($this->view->render('sitemap', ['h' => $h, 'products' => $products]));
    }

    private function simplePage(Request $request, string $template, string $title, string $pageId): Response
    {
        $prefs = $this->preferences->fromRequest($request);
        $criteria = $this->criteria->fromRequest($request, $prefs);

        return $this->view->page($template, [
            'h' => $this->view->helpers($request, $prefs, $criteria->location),
            'prefs' => $prefs,
            'criteria' => $criteria,
            'title' => $title,
            'pageId' => $pageId,
        ]);
    }
}
