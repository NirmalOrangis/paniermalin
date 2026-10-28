<?php
/**
 * @var App\View\ViewHelpers                  $h
 * @var App\Domain\Model\Promotion            $reference
 * @var App\Domain\Model\ProductOffers|null   $offers     offers in the visitor's zone
 * @var App\Domain\Model\ProductOffers|null   $elsewhere  offers elsewhere in France when none locally
 * @var list<App\Domain\Model\ProductOffers>  $similar
 */
$category = $h->category($reference->categoryId);
$shown = $offers ?? $elsewhere;
?>
<nav class="breadcrumb" aria-label="Fil d'Ariane">
    <a href="<?= $h->e($h->url('/')) ?>">Accueil</a> ›
    <a href="<?= $h->e($h->url('/promos', ['cat' => $reference->categoryId])) ?>"><?= $h->e($category?->name ?? '') ?></a> ›
    <span aria-current="page"><?= $h->e($reference->name) ?></span>
</nav>

<section class="product" data-track-category="<?= $h->e($reference->categoryId) ?>">
    <div class="product__media" style="--cat-bg:<?= $h->e($category?->color ?? '#eef0f3') ?>" aria-hidden="true"><?= $category?->emoji ?? '🛒' ?></div>
    <div class="product__info">
        <p class="muted"><?= $h->e($reference->brand) ?></p>
        <h1 class="page-title"><?= $h->e($reference->name) ?></h1>
        <p class="muted"><?= $h->e($reference->packLabel) ?></p>

        <?php if ($offers !== null): ?>
            <p class="product__best">
                Le moins cher : <?= $h->storeBadge($offers->cheapest()->storeId) ?>
                <strong class="product__best-price"><?= $h->price($offers->cheapest()->price) ?></strong>
            </p>
            <?php if ($offers->storeCount() > 1): ?>
                <p class="saving-banner">
                    💰 Vous économisez <strong><?= $h->price($offers->spread()) ?></strong> (−<?= $offers->spreadPercent() ?>&nbsp;%)
                    en choisissant <?= $h->e($h->storeName($offers->cheapest()->storeId)) ?> plutôt que <?= $h->e($h->storeName($offers->priciest()->storeId)) ?>.
                </p>
            <?php endif; ?>
        <?php else: ?>
            <p class="notice">Pas de promotion sur ce produit dans votre zone (<?= $h->e($h->location()?->label() ?? '') ?>) en ce moment. Voici les offres ailleurs en France.</p>
        <?php endif; ?>

        <button type="button" class="btn btn--primary" data-add="<?= $h->e($reference->productKey) ?>" data-name="<?= $h->e(trim($reference->brand . ' ' . $reference->name)) ?>" data-cat="<?= $h->e($reference->categoryId) ?>" aria-pressed="false">
            ＋ Ajouter à ma liste
        </button>
    </div>
</section>

<?php if ($shown !== null): ?>
    <section class="section">
        <h2 class="section__title"><?= $h->plural($shown->storeCount(), 'offre en cours', 'offres en cours') ?>, du moins cher au plus cher</h2>
        <ol class="offers">
            <?php foreach ($shown->offers as $i => $offer): ?>
                <li class="offer<?= $i === 0 ? ' is-best' : '' ?>">
                    <span class="offer__rank" aria-label="Rang <?= $i + 1 ?>"><?= $i + 1 ?></span>
                    <span class="offer__store"><?= $h->storeBadge($offer->storeId) ?></span>
                    <span class="offer__price">
                        <strong><?= $h->price($offer->price) ?></strong>
                        <s><?= $h->price($offer->regularPrice) ?></s>
                        <span class="pill">−<?= $offer->discountPercent() ?>&nbsp;%</span>
                    </span>
                    <span class="offer__mechanic<?= $offer->loyaltyCard ? ' is-loyalty' : '' ?>"><?= $offer->loyaltyCard ? '💳 ' : '' ?><?= $h->e($offer->mechanic ?? 'Remise immédiate') ?></span>
                    <span class="offer__unit"><?= $h->unitPrice($offer) ?><?= $offer->minQuantity > 1 ? ' · par lot de ' . $offer->minQuantity : '' ?></span>
                    <?php if ($offer->isFieldObservation()): ?>
                        <span class="offer__dates">📸 <?= $h->e($h->freshnessLabel($offer)) ?> par un contributeur <a href="https://prices.openfoodfacts.org/" rel="noopener">Open Prices</a> · prix constaté dans ce magasin, à vérifier</span>
                        <span class="offer__zones">📍 <?= $h->e($offer->place ?? $h->zonesLabel($offer->zones)) ?></span>
                    <?php else: ?>
                        <span class="offer__dates">Du <?= $h->e($h->date($offer->startsAt)) ?> au <?= $h->e($h->date($offer->endsAt)) ?> · <span class="<?= $h->daysLeft($offer) <= 1 ? 'is-urgent' : '' ?>"><?= $h->e($h->endsLabel($offer)) ?></span></span>
                        <span class="offer__zones">📍 <?= $h->e($h->zonesLabel($offer->zones)) ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
        <p class="muted small">Prix « par lot » : prix moyen par article quand l'offre impose d'en acheter plusieurs. Offres carte : montant crédité sur votre carte de fidélité.</p>
    </section>
<?php endif; ?>

<?php if ($similar): ?>
    <section class="section">
        <h2 class="section__title">Dans la même catégorie</h2>
        <div class="grid grid--cmp">
            <?php foreach ($similar as $g) { include __DIR__ . '/partials/compare-card.php'; } ?>
        </div>
    </section>
<?php endif; ?>
