<?php

declare(strict_types=1);

namespace App;

use App\Controller\ApiController;
use App\Controller\CompareController;
use App\Controller\HomeController;
use App\Controller\PageController;
use App\Controller\PromotionController;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Infrastructure\CompositePromotionRepository;
use App\Infrastructure\JsonCatalogRepository;
use App\Preferences\PreferencesResolver;
use App\Service\BasketOptimizer;
use App\Service\CriteriaFactory;
use App\Service\LocationMatcher;
use App\Service\PriceComparisonService;
use App\Service\PromotionSearchService;
use App\Support\Clock;
use App\View\View;
use App\View\ViewHelpers;

/** Wires the services (manual dependency injection) and dispatches requests. */
final class Kernel
{
    private const SECURITY_HEADERS = [
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'X-Frame-Options' => 'DENY',
        'Permissions-Policy' => 'geolocation=(), camera=(), microphone=()',
        'Content-Security-Policy' => "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'",
    ];

    private readonly Router $router;
    private readonly PageController $pages;

    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config)
    {
        $clock = new Clock();
        $catalog = new JsonCatalogRepository($config['data_dir']);
        $promotions = CompositePromotionRepository::fromConfig($config, $catalog, $clock);
        $search = new PromotionSearchService($promotions, $catalog, new LocationMatcher($catalog), $clock);
        $comparison = new PriceComparisonService($search);
        $preferences = new PreferencesResolver($catalog);
        $criteria = new CriteriaFactory($catalog, $config['default_location'], $config['per_page']);
        $view = new View(APP_ROOT . '/templates', new ViewHelpers($config, $catalog, $clock));

        $home = new HomeController($view, $preferences, $criteria, $search, $comparison);
        $promos = new PromotionController($view, $preferences, $criteria, $search);
        $compare = new CompareController($view, $preferences, $criteria, $comparison);
        $api = new ApiController($view, $preferences, $criteria, $search, $comparison, new BasketOptimizer($comparison));
        $this->pages = new PageController($view, $preferences, $criteria, $search);

        $this->router = (new Router())
            ->get('/', $home->index(...))
            ->get('/promos', $promos->index(...))
            ->get('/comparer', $compare->index(...))
            ->get('/comparer/{key}', $compare->show(...))
            ->get('/liste', $this->pages->shoppingList(...))
            ->get('/confidentialite', $this->pages->privacy(...))
            ->get('/sitemap.xml', $this->pages->sitemap(...))
            ->get('/api/promotions', $api->promotions(...))
            ->get('/api/suggest', $api->suggest(...))
            ->get('/api/basket', $api->basket(...));
    }

    public function handle(Request $request): Response
    {
        try {
            $match = $this->router->match($request);
            $response = $match === null
                ? $this->pages->notFound($request)
                : ($match[0])($request, ...$match[1]);
        } catch (\Throwable $e) {
            error_log((string) $e);
            $message = $this->config['debug'] ? htmlspecialchars((string) $e) : 'Une erreur est survenue. Merci de réessayer dans un instant.';
            $response = Response::html('<!doctype html><meta charset="utf-8"><title>Erreur</title><pre style="font:16px system-ui;padding:2rem;white-space:pre-wrap">' . $message . '</pre>', 500);
        }

        return $response->withHeaders(self::SECURITY_HEADERS);
    }
}
