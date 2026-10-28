<?php
/**
 * @var App\View\ViewHelpers           $h
 * @var App\Domain\Model\ProductOffers $g
 */
$ref = $g->cheapest();
$category = $h->category($ref->categoryId);
$url = $h->url('/comparer/' . $g->productKey);
?>
<article class="cmp">
    <a class="cmp__head" href="<?= $h->e($url) ?>" data-cat-track="<?= $h->e($ref->categoryId) ?>">
        <span class="cmp__emoji" style="--cat-bg:<?= $h->e($category?->color ?? '#eef0f3') ?>" aria-hidden="true"><?= $category?->emoji ?? '🛒' ?></span>
        <span>
            <strong class="cmp__name"><?= $h->e($ref->name) ?></strong>
            <span class="cmp__meta"><?= $h->e(implode(' · ', array_filter([$ref->brand, $ref->packLabel]))) ?></span>
        </span>
    </a>
    <ol class="cmp__list">
        <?php foreach (array_slice($g->offers, 0, 4) as $i => $offer): ?>
            <li class="<?= $i === 0 ? 'is-best' : '' ?>">
                <?= $h->storeBadge($offer->storeId) ?>
                <span class="cmp__price"><?= $h->price($offer->price) ?></span>
                <?php if ($i === 0): ?><span class="pill pill--good">Le moins cher</span><?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
    <?php if ($g->storeCount() > 1): ?>
        <p class="cmp__saving">Jusqu'à <b><?= $h->price($g->spread()) ?></b> d'écart (−<?= $g->spreadPercent() ?>&nbsp;%)</p>
    <?php endif; ?>
    <div class="cmp__actions">
        <a class="btn btn--soft btn--sm" href="<?= $h->e($url) ?>" data-cat-track="<?= $h->e($ref->categoryId) ?>">Comparer les <?= $g->storeCount() ?> offres</a>
        <button type="button" class="btn btn--icon" data-add="<?= $h->e($g->productKey) ?>" data-name="<?= $h->e(trim($ref->brand . ' ' . $ref->name)) ?>" data-cat="<?= $h->e($ref->categoryId) ?>"
                aria-pressed="false" aria-label="Ajouter « <?= $h->e($ref->name) ?> » à ma liste" title="Ajouter à ma liste"><span aria-hidden="true">＋</span></button>
    </div>
</article>
