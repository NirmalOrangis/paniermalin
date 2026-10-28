<?php
/**
 * @var App\View\ViewHelpers                  $h
 * @var string                                $query
 * @var list<App\Domain\Model\ProductOffers>  $products
 * @var list<App\Domain\Model\Category>       $categories
 */
?>
<?php foreach ($categories as $cat): ?>
    <a class="suggest__item" role="option" href="<?= $h->e($h->url('/promos', ['cat' => $cat->id])) ?>" data-cat-track="<?= $h->e($cat->id) ?>">
        <span class="suggest__emoji" aria-hidden="true"><?= $cat->emoji ?></span>
        <span class="suggest__text"><strong><?= $h->e($cat->name) ?></strong><span class="muted">Catégorie</span></span>
    </a>
<?php endforeach; ?>
<?php foreach ($products as $g): ?>
    <?php $ref = $g->cheapest(); ?>
    <a class="suggest__item" role="option" href="<?= $h->e($h->url('/comparer/' . $g->productKey)) ?>" data-cat-track="<?= $h->e($ref->categoryId) ?>">
        <span class="suggest__emoji" aria-hidden="true"><?= $h->category($ref->categoryId)?->emoji ?? '🛒' ?></span>
        <span class="suggest__text">
            <strong><?= $h->e($ref->name) ?></strong>
            <span class="muted"><?= $h->e(implode(' · ', array_filter([$ref->brand, $ref->packLabel]))) ?></span>
        </span>
        <span class="suggest__price">dès <?= $h->price($ref->price) ?><small><?= $h->plural($g->storeCount(), 'enseigne', 'enseignes') ?></small></span>
    </a>
<?php endforeach; ?>
<a class="suggest__item suggest__item--all" role="option" href="<?= $h->e($h->url('/promos', ['q' => $query])) ?>">
    🔍 Toutes les promos pour « <?= $h->e($query) ?> »
</a>
