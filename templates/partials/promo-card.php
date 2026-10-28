<?php
/**
 * @var App\View\ViewHelpers           $h
 * @var App\Domain\Model\Promotion     $p
 * @var array<string, int>             $offerCounts
 */
$category = $h->category($p->categoryId);
$offers = $offerCounts[$p->productKey] ?? 1;
$compareUrl = $h->url('/comparer/' . $p->productKey);
$daysLeft = $h->daysLeft($p);
?>
<article class="card" style="--cat-bg:<?= $h->e($category?->color ?? '#eef0f3') ?>">
    <a class="card__media" href="<?= $h->e($compareUrl) ?>" data-cat-track="<?= $h->e($p->categoryId) ?>" tabindex="-1" aria-hidden="true">
        <span class="card__emoji"><?= $category?->emoji ?? '🛒' ?></span>
    </a>
    <span class="card__discount">-<?= $p->discountPercent() ?>&nbsp;%</span>
    <div class="card__body">
        <?= $h->storeBadge($p->storeId) ?>
        <h3 class="card__title"><a href="<?= $h->e($compareUrl) ?>" data-cat-track="<?= $h->e($p->categoryId) ?>"><?= $h->e($p->name) ?></a></h3>
        <p class="card__meta"><?= $h->e(implode(' · ', array_filter([$p->brand, $p->packLabel]))) ?></p>
        <p class="card__mechanic<?= $p->loyaltyCard ? ' is-loyalty' : '' ?>">
            <?= $p->loyaltyCard ? '💳 ' : '' ?><?= $h->e($p->mechanic ?? 'Remise immédiate') ?>
        </p>
        <p class="card__price">
            <strong><?= $h->price($p->price) ?></strong>
            <s aria-label="Prix habituel"><?= $h->price($p->regularPrice) ?></s>
        </p>
        <p class="card__unit">
            <?= $h->unitPrice($p) ?><?php if ($p->minQuantity > 1): ?> · <span title="Prix moyen par article en achetant <?= $p->minQuantity ?>">par lot de <?= $p->minQuantity ?></span><?php endif; ?>
        </p>
        <?php if ($p->isFieldObservation()): ?>
            <p class="card__ends" title="Prix relevé en magasin par un contributeur Open Prices">📸 <?= $h->e($h->freshnessLabel($p)) ?></p>
            <p class="card__place">📍 <?= $h->e($p->place) ?></p>
        <?php else: ?>
            <p class="card__ends<?= $daysLeft <= 1 ? ' is-urgent' : '' ?>">⏰ <?= $h->e($h->endsLabel($p)) ?></p>
        <?php endif; ?>
    </div>
    <div class="card__actions">
        <a class="btn btn--soft btn--sm" href="<?= $h->e($compareUrl) ?>" data-cat-track="<?= $h->e($p->categoryId) ?>">
            <?= $offers > 1 ? '⚖️ ' . $offers . ' enseignes' : 'Détail' ?>
        </a>
        <button type="button" class="btn btn--icon" data-add="<?= $h->e($p->productKey) ?>" data-name="<?= $h->e(trim($p->brand . ' ' . $p->name)) ?>" data-cat="<?= $h->e($p->categoryId) ?>"
                aria-pressed="false" aria-label="Ajouter « <?= $h->e($p->name) ?> » à ma liste" title="Ajouter à ma liste">
            <span aria-hidden="true">＋</span>
        </button>
    </div>
</article>
