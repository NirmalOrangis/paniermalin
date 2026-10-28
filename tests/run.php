<?php

/*
 * Dependency-free test runner: php tests/run.php
 * (Swap for PHPUnit once the project grows.)
 */

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/src/bootstrap.php';

use App\Domain\Model\Promotion;
use App\Domain\Search\PromotionCriteria;
use App\Http\Request;
use App\Infrastructure\JsonCatalogRepository;
use App\Infrastructure\JsonPromotionRepository;
use App\Kernel;
use App\Preferences\PreferencesResolver;
use App\Service\BasketOptimizer;
use App\Service\LocationMatcher;
use App\Service\PriceComparisonService;
use App\Service\PromotionSearchService;
use App\Support\Clock;
use App\Support\Text;

$failures = 0;
$count = 0;
function check(string $label, bool $condition): void
{
    global $failures, $count;
    $count++;
    echo ($condition ? "  ✓ " : "  ✗ ") . $label . "\n";
    if (!$condition) {
        $failures++;
    }
}

function promo(array $overrides = []): Promotion
{
    $args = array_merge([
        'id' => 'x', 'productKey' => 'p', 'name' => 'Produit', 'brand' => 'Marque', 'categoryId' => 'boissons',
        'storeId' => 'lidl', 'price' => 3.0, 'regularPrice' => 4.0, 'quantity' => 0.5, 'unit' => 'kg', 'packLabel' => '500 g',
        'mechanic' => null, 'minQuantity' => 1, 'loyaltyCard' => false,
        'startsAt' => new DateTimeImmutable('2026-10-20'), 'endsAt' => new DateTimeImmutable('2026-10-30 23:59:59'), 'zones' => ['FR'],
    ], $overrides);

    return new Promotion(...$args);
}

$clock = new Clock('2026-10-28 12:00');
$catalog = new JsonCatalogRepository(APP_ROOT . '/data');
$search = new PromotionSearchService(new JsonPromotionRepository(APP_ROOT . '/data/promotions.json', $clock), $catalog, new LocationMatcher($catalog), $clock);
$comparison = new PriceComparisonService($search);

echo "Promotion\n";
$p = promo();
check('discount percent', $p->discountPercent() === 25);
check('unit price per kg', abs($p->unitPrice() - 6.0) < 0.001);
check('active inside its dates', $p->isActiveAt($clock->now()));
check('days left', $p->daysLeft($clock->now()) === 2);
check('inactive after end', !$p->isActiveAt(new DateTimeImmutable('2026-10-31')));

echo "Text\n";
check('normalize strips accents', Text::normalize('Crème Brûlée') === 'creme brulee');
check('plural tokens', Text::searchTokens('Pommes') === ['pomme']);

echo "LocationMatcher\n";
$matcher = new LocationMatcher($catalog);
check('national offer everywhere', $matcher->matches(['FR'], '91'));
check('regional offer in its department', $matcher->matches(['IDF'], '91'));
check('department offer when browsing its region', $matcher->matches(['91'], 'IDF'));
check('other region excluded', !$matcher->matches(['PAC'], '91'));
check('other department excluded', !$matcher->matches(['75'], '91'));
check('whole France sees everything', $matcher->matches(['13'], 'FR'));

echo "Search\n";
$all = $search->search(new PromotionCriteria('FR', perPage: 1000));
check('demo data loaded', $all->total > 100);
$idf = $search->search(new PromotionCriteria('IDF', perPage: 1000));
check('IDF is a subset of France', $idf->total > 0 && $idf->total < $all->total);
$lidl = $search->search(new PromotionCriteria('FR', stores: ['lidl'], perPage: 1000));
check('store filter', $lidl->total > 0 && array_unique(array_map(fn ($p) => $p->storeId, $lidl->items)) === ['lidl']);
check('store facets ignore the store filter', count($lidl->storeFacets) > 1);
$nutella = $search->search(new PromotionCriteria('FR', query: 'nutela'));
check('typo returns nothing rather than garbage', $nutella->total === 0);
$cafe = $search->search(new PromotionCriteria('FR', query: 'CAFÉ moulu'));
check('accent-insensitive search', $cafe->total > 0);
$byDiscount = $search->search(new PromotionCriteria('FR', sort: 'remise', perPage: 50))->items;
check('sorted by discount', $byDiscount[0]->discountPercent() >= $byDiscount[49]->discountPercent());
$noLoyalty = $search->search(new PromotionCriteria('FR', includeLoyalty: false, perPage: 1000));
check('loyalty offers can be excluded', array_filter($noLoyalty->items, fn ($p) => $p->loyaltyCard) === []);
$page2 = $search->search(new PromotionCriteria('FR', page: 2, perPage: 10));
check('pagination', count($page2->items) === 10 && $page2->hasMore());

echo "Comparison & basket\n";
$gaps = $comparison->biggestGaps(new PromotionCriteria('FR'), 5);
check('gaps have at least two stores, cheapest first', $gaps !== [] && $gaps[0]->storeCount() >= 2 && $gaps[0]->cheapest()->price <= $gaps[0]->priciest()->price);
$keys = array_slice(array_map(fn ($g) => $g->productKey, $gaps), 0, 3);
$plan = (new BasketOptimizer($comparison))->plan([...$keys, 'unknown-product'], new PromotionCriteria('FR'));
check('basket reports missing products', $plan->missing() === ['unknown-product']);
check('best mix is never dearer than any single store with full coverage', array_reduce(
    $plan->stores,
    fn (bool $ok, array $s) => $ok && ($s['found'] < $plan->availableCount() || $plan->bestMixTotal <= $s['total'] + 0.001),
    true,
));

echo "Preferences\n";
$resolver = new PreferencesResolver($catalog);
$noConsent = $resolver->fromRequest(new Request('GET', '/', [], ['pm_prefs' => '{"loc":"91"}']));
check('preferences ignored without consent', $noConsent->location === null);
$prefs = $resolver->fromRequest(new Request('GET', '/', [], ['pm_consent' => '1', 'pm_prefs' => '{"loc":"91","stores":["lidl","hacker"],"int":{"bebe":5,"boissons":9,"nope":3}}']));
check('valid location kept', $prefs->location === '91');
check('unknown stores dropped', $prefs->stores === ['lidl']);
check('interests ranked', $prefs->topInterests(2) === ['boissons', 'bebe']);

echo "Manual CSV source\n";
$csv = tempnam(sys_get_temp_dir(), 'pm') . '.csv';
file_put_contents($csv, "\u{FEFF}# commentaire ignoré\n"
    . "product;name;brand;category;store;price;regular_price;quantity;unit;pack;mechanic;min_quantity;loyalty_card;starts;ends;zones\n"
    . "3017620422003;Pâte à tartiner;Nutella;epicerie-sucree;leclerc;3,29;4,19;400;g;400 g;;1;oui;27/10/2026;02/11/2026;91 92\n"
    . "x;Produit;;epicerie-sucree;inconnu;1;2;;;;;;;2026-10-27;2026-11-02;FR\n"
    . "y;Produit;;epicerie-sucree;lidl;3;2;;;;;;;2026-10-27;2026-11-02;FR\n");
$manual = new App\Infrastructure\CsvPromotionRepository($csv, $catalog);
$rows = $manual->findAll();
check('valid CSV line imported', count($rows) === 1 && $rows[0]->storeId === 'leclerc' && $rows[0]->source === 'manual');
check('decimal comma and grams converted', abs($rows[0]->price - 3.29) < 0.001 && abs($rows[0]->quantity - 0.4) < 0.001 && $rows[0]->unit === 'kg');
check('French dates, zones and loyalty parsed', $rows[0]->endsAt->format('Y-m-d') === '2026-11-02' && $rows[0]->zones === ['91', '92'] && $rows[0]->loyaltyCard);
check('invalid lines reported, not fatal', count($manual->errors()) === 2 && str_contains($manual->errors()[0], 'enseigne inconnue') && str_contains($manual->errors()[1], 'prix incohérents'));
unlink($csv);
check('missing CSV file means no promotions', (new App\Infrastructure\CsvPromotionRepository('/nope.csv', $catalog))->findAll() === []);

echo "Open Prices mapper\n";
$mapper = new App\Infrastructure\OpenPrices\OpenPricesMapper(7);
$item = [
    'id' => 42, 'type' => 'PRODUCT', 'price' => 2.36, 'price_is_discounted' => true, 'price_without_discount' => 2.95,
    'discount_type' => 'LOYALTY_PROGRAM', 'currency' => 'EUR', 'date' => '2026-10-26', 'owner' => 'someone',
    'product' => ['code' => '3038359011467', 'source' => 'off', 'product_name' => 'Sauce provençale', 'brands' => 'PANZANI,Panzani SA',
        'product_quantity' => 650, 'product_quantity_unit' => 'g', 'quantity' => '650 g', 'categories_tags' => ['en:plant-based-foods', 'en:sauces']],
    'location' => ['osm_name' => 'Franprix', 'osm_brand' => 'Franprix', 'osm_address_postcode' => '93200', 'osm_address_city' => 'Saint-Denis', 'osm_address_country_code' => 'FR'],
];
$record = $mapper->map($item, $reason);
check('maps a French supermarket price', $record !== null && $record['store'] === 'franprix' && $record['zones'] === ['93'] && $record['product'] === '3038359011467');
check('7-day validity from the observation date', $record['startsAt'] === '2026-10-26' && $record['endsAt'] === '2026-11-01');
check('loyalty discount, unit and brand normalized', $record['loyaltyCard'] && $record['unit'] === 'kg' && abs($record['quantity'] - 0.65) < 0.001 && $record['brand'] === 'Panzani');
check('contributor name not copied', !str_contains(json_encode($record), 'someone'));
$mapper->map(['location' => ['osm_name' => 'Biocoop', 'osm_brand' => 'Biocoop'] + $item['location']] + $item, $reason);
check('untracked store skipped with reason', $reason === 'enseigne non suivie');
$mapper->map(['discount_type' => 'EXPIRES_SOON'] + $item, $reason);
check('short-date markdowns skipped', $reason === 'pas une promotion');
$mapper->map(['price_without_discount' => null] + $item, $reason);
check('no crossed-out price skipped', $reason === 'prix barré absent');
$loose = $mapper->map(['product' => null, 'category_tag' => 'en:tomatoes', 'price_per' => 'KILOGRAM'] + $item);
check('loose produce mapped to French name per kg', $loose !== null && $loose['name'] === 'Tomates' && $loose['unit'] === 'kg' && $loose['category'] === 'fruits-legumes');
check('postcodes to departments', App\Infrastructure\OpenPrices\OpenPricesMapper::department('20100') === '2A'
    && App\Infrastructure\OpenPrices\OpenPricesMapper::department('20200') === '2B'
    && App\Infrastructure\OpenPrices\OpenPricesMapper::department('97411') === '974'
    && App\Infrastructure\OpenPrices\OpenPricesMapper::department('91300') === '91');
$barcodeSearch = new PromotionSearchService(new App\Infrastructure\CompositePromotionRepository(['t' => new class () implements App\Domain\Repository\PromotionRepositoryInterface {
    public function findAll(): array
    {
        return [promo(['id' => 'a', 'productKey' => '3038359011467', 'storeId' => 'lidl']), promo(['id' => 'b', 'productKey' => '3038359011467', 'storeId' => 'aldi', 'price' => 2.5])];
    }
}]), $catalog, new LocationMatcher($catalog), $clock);
$barcodeComparison = new PriceComparisonService($barcodeSearch);
$gap = $barcodeComparison->biggestGaps(new PromotionCriteria('FR'), 1);
check('numeric barcodes work as product keys', $gap !== [] && $gap[0]->productKey === '3038359011467' && $gap[0]->cheapest()->storeId === 'aldi');
check('missing barcode stays a string', (new BasketOptimizer($barcodeComparison))->plan(['3000000000000'], new PromotionCriteria('FR'))->missing() === ['3000000000000']);
check('field observations are flagged', promo(['source' => 'open-prices'])->isFieldObservation() && !promo()->isFieldObservation());

echo "HTTP\n";
$kernel = new Kernel(require APP_ROOT . '/config/app.php');
foreach (['/', '/promos', '/promos?cat=boissons&sort=prix', '/comparer', '/comparer?q=lait', '/liste', '/confidentialite', '/sitemap.xml', '/api/promotions?loc=91', '/api/suggest?q=caf', '/api/basket?items=nutella-750g,spaghetti-500g'] as $url) {
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
    $response = $kernel->handle(new Request('GET', (string) parse_url($url, PHP_URL_PATH), $query));
    check("GET {$url} → {$response->status()}", in_array($response->status(), [200, 302], true));
}
check('unknown page is a 404', $kernel->handle(new Request('GET', '/nope'))->status() === 404);
check('unknown product is a 404', $kernel->handle(new Request('GET', '/comparer/nope'))->status() === 404);
$json = json_decode($kernel->handle(new Request('GET', '/api/promotions', ['q' => 'nutella']))->body(), true);
check('API returns html and items', isset($json['html'], $json['items']) && $json['total'] === count($json['items']));
$html = $kernel->handle(new Request('GET', '/promos', ['q' => '<script>alert(1)</script>']))->body();
check('user input is escaped', !str_contains($html, '<script>alert(1)</script>'));

echo "\n" . ($failures === 0 ? "All {$count} checks passed.\n" : "{$failures}/{$count} checks FAILED.\n");
exit($failures === 0 ? 0 : 1);
