<?php
/**
 * @var App\View\ViewHelpers                  $h
 * @var App\Domain\Search\PromotionCriteria   $criteria
 * @var list<App\Domain\Model\ProductOffers>  $products
 * @var list<App\Domain\Model\ProductOffers>  $gaps
 * @var string                                $query
 */
?>
<section class="hero hero--compact">
    <h1 class="hero__title">⚖️ Comparateur de prix</h1>
    <p class="hero__text">Un produit, toutes les enseignes : trouvez où il est le moins cher en ce moment<?= $h->location()?->type !== 'country' ? ' en ' . $h->e($h->location()?->name) : '' ?>.</p>
    <form class="compare-search" action="/comparer" method="get">
        <input type="hidden" name="loc" value="<?= $h->e($h->locationCode()) ?>">
        <label class="visually-hidden" for="compare-q">Produit à comparer</label>
        <input id="compare-q" class="input input--lg" type="search" name="q" value="<?= $h->e($query) ?>" placeholder="Quel produit voulez-vous comparer ?" maxlength="80">
        <button class="btn btn--primary" type="submit">Comparer</button>
    </form>
</section>

<?php if ($query !== ''): ?>
    <section class="section">
        <h2 class="section__title"><?= $products ? $h->plural(count($products), 'produit trouvé', 'produits trouvés') : 'Aucun produit trouvé' ?> pour « <?= $h->e($query) ?> »</h2>
        <?php if (!$products): ?>
            <p class="muted">Vérifiez l'orthographe ou essayez un terme plus général (ex. « lait », « café », « couches »).</p>
        <?php endif; ?>
        <div class="grid grid--cmp">
            <?php foreach ($products as $g) { include __DIR__ . '/partials/compare-card.php'; } ?>
        </div>
    </section>
<?php else: ?>
    <section class="section">
        <div class="section__head">
            <div>
                <h2 class="section__title">Les plus gros écarts de prix du moment</h2>
                <p class="section__subtitle">Sur ces produits, choisir la bonne enseigne fait vraiment la différence.</p>
            </div>
        </div>
        <div class="grid grid--cmp">
            <?php foreach ($gaps as $g) { include __DIR__ . '/partials/compare-card.php'; } ?>
        </div>
    </section>
<?php endif; ?>
