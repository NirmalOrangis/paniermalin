<?php
/**
 * @var App\View\ViewHelpers                      $h
 * @var App\Preferences\UserPreferences           $prefs
 * @var App\Domain\Search\PromotionCriteria       $criteria
 * @var App\Domain\Search\SearchResult            $overview
 * @var App\Domain\Search\SearchResult|null       $forYou
 * @var App\Domain\Search\SearchResult            $topDeals
 * @var list<App\Domain\Model\ProductOffers>      $gaps
 * @var App\Domain\Search\SearchResult            $endingSoon
 * @var App\Domain\Search\SearchResult|null       $discover
 * @var array<string, int>                        $offerCounts
 */
$locationName = $h->location()?->name ?? 'France';
$storeNames = array_map($h->storeName(...), $criteria->stores);
$storeFilter = $criteria->stores ? ['stores' => implode(',', $criteria->stores)] : [];
?>
<section class="hero">
    <h1 class="hero__title">Les meilleures promos <span>
        <?= $h->location()?->type === 'country' ? 'de France' : 'en ' . $h->e($locationName) ?></span>, comparées pour vous.</h1>
    <p class="hero__text">
        Carrefour, Auchan, E.Leclerc, Lidl, Intermarché, Aldi… <strong><?= $h->plural($overview->total, 'promotion', 'promotions') ?></strong>
        en cours dans <strong><?= count($overview->storeFacets) ?> enseignes</strong>. Trouvez le prix le plus bas en un coup d'œil.
    </p>

    <?php if ($prefs->isReturning()): ?>
        <p class="welcome">
            👋 Bon retour ! Vos promos pour <strong><?= $h->e($locationName) ?></strong>
            <?php if ($storeNames): ?>chez <strong><?= $h->e(implode(', ', $storeNames)) ?></strong><?php endif; ?>.
            <a href="<?= $h->e($h->url('/promos')) ?>">Modifier mes filtres</a>
        </p>
    <?php endif; ?>

    <nav class="chips" aria-label="Catégories">
        <?php foreach ($h->categories() as $cat): ?>
            <?php $count = $overview->categoryFacets[$cat->id] ?? 0; ?>
            <?php if ($count === 0) { continue; } ?>
            <a class="chip" href="<?= $h->e($h->url('/promos', ['cat' => $cat->id])) ?>" data-cat-track="<?= $h->e($cat->id) ?>">
                <span aria-hidden="true"><?= $cat->emoji ?></span> <?= $h->e($cat->name) ?> <span class="chip__count"><?= $count ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
</section>

<?php
if ($forYou !== null) {
    $interestNames = array_map(fn (string $id): string => $h->category($id)?->name ?? $id, $prefs->topInterests(3));
    $sectionTitle = '✨ Pour vous';
    $sectionSubtitle = 'Selon vos centres d\'intérêt : ' . implode(', ', $interestNames);
    $sectionLink = $h->url('/promos', ['cat' => implode(',', $prefs->topInterests(3))] + $storeFilter);
    $items = $forYou->items;
    include __DIR__ . '/partials/section.php';
}

$sectionTitle = '🔥 Les plus fortes remises';
$sectionSubtitle = $storeNames ? 'Chez vos enseignes : ' . implode(', ', $storeNames) : 'Toutes enseignes confondues';
$sectionLink = $h->url('/promos', ['sort' => 'remise'] + $storeFilter);
$items = $topDeals->items;
include __DIR__ . '/partials/section.php';
?>

<?php if ($gaps): ?>
    <section class="section">
        <div class="section__head">
            <div>
                <h2 class="section__title">⚖️ Là où comparer rapporte le plus</h2>
                <p class="section__subtitle">Même produit, prix très différents selon l'enseigne.</p>
            </div>
            <a class="section__more" href="<?= $h->e($h->url('/comparer')) ?>">Comparateur →</a>
        </div>
        <div class="grid grid--cmp">
            <?php foreach ($gaps as $g) { include __DIR__ . '/partials/compare-card.php'; } ?>
        </div>
    </section>
<?php endif; ?>

<?php
$sectionTitle = '⏰ Dernière chance';
$sectionSubtitle = 'Ces promos se terminent très bientôt.';
$sectionLink = $h->url('/promos', ['sort' => 'fin'] + $storeFilter);
$items = $endingSoon->items;
include __DIR__ . '/partials/section.php';

if ($discover !== null) {
    $sectionTitle = '🧭 À découvrir aussi';
    $sectionSubtitle = 'D\'autres bons plans, dans toutes les enseignes.';
    $sectionLink = $h->url('/promos', ['sort' => 'remise', 'stores' => '']);
    $items = $discover->items;
    include __DIR__ . '/partials/section.php';
}
?>

<section class="section">
    <div class="section__head">
        <div>
            <h2 class="section__title">🏪 Les enseignes</h2>
            <p class="section__subtitle">Nombre de promotions en cours dans votre zone.</p>
        </div>
    </div>
    <div class="stores">
        <?php foreach ($h->stores() as $store): ?>
            <?php $count = $overview->storeFacets[$store->id] ?? 0; ?>
            <a class="store-tile<?= $count === 0 ? ' is-empty' : '' ?>" href="<?= $h->e($h->url('/promos', ['stores' => $store->id])) ?>">
                <?= $h->storeBadge($store->id, 'store--lg') ?>
                <span class="store-tile__format"><?= $h->e($store->format) ?></span>
                <span class="store-tile__count"><?= $h->plural($count, 'promo', 'promos') ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="section how">
    <h2 class="section__title">Comment ça marche ?</h2>
    <ol class="how__steps">
        <li><span aria-hidden="true">📍</span><strong>Choisissez votre zone</strong> — département ou région, les promos locales s'affichent.</li>
        <li><span aria-hidden="true">⚖️</span><strong>Comparez</strong> — le même produit dans toutes les enseignes, du moins cher au plus cher.</li>
        <li><span aria-hidden="true">📝</span><strong>Composez votre liste</strong> — on calcule le magasin le plus avantageux pour tout votre panier.</li>
    </ol>
</section>
