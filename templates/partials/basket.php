<?php
/**
 * @var App\View\ViewHelpers          $h
 * @var App\Domain\Model\BasketPlan   $plan
 */
$best = $plan->bestSingleStore();
$available = $plan->availableCount();
$mixStores = count($plan->bestMix);
?>
<div class="basket">
    <?php if ($available > 0 && $best !== null): ?>
        <div class="basket__summary">
            <div class="basket__card basket__card--mix">
                <p class="basket__label">🧠 Meilleure combinaison</p>
                <p class="basket__total"><?= $h->price($plan->bestMixTotal) ?></p>
                <p class="muted"><?= $h->plural($available, 'produit', 'produits') ?> en promo · <?= $h->plural($mixStores, 'enseigne', 'enseignes') ?></p>
            </div>
            <div class="basket__card">
                <p class="basket__label">🏪 Tout dans un seul magasin</p>
                <?php if ($best['found'] === $available): ?>
                    <p class="basket__total"><?= $h->price($best['total']) ?></p>
                    <p class="muted"><?= $h->storeBadge($best['store']) ?> a tous vos produits en promo
                        <?php if ($best['total'] > $plan->bestMixTotal): ?>(+<?= $h->price($best['total'] - $plan->bestMixTotal) ?> par rapport à la combinaison)<?php endif; ?></p>
                <?php else: ?>
                    <p class="basket__partial">Aucune enseigne n'a tous vos produits en promo.</p>
                    <p class="muted">La plus complète : <?= $h->storeBadge($best['store']) ?> avec <?= $best['found'] ?>/<?= $available ?> produits pour <?= $h->price($best['total']) ?>.</p>
                <?php endif; ?>
            </div>
        </div>

        <section class="section">
            <h2 class="section__title">Où acheter quoi (combinaison la moins chère)</h2>
            <div class="basket__groups">
                <?php foreach ($plan->bestMix as $storeId => $offers): ?>
                    <div class="basket__group">
                        <p class="basket__group-head"><?= $h->storeBadge($storeId) ?> <span class="muted"><?= $h->price(array_sum(array_map(static fn ($o) => $o->price, $offers))) ?></span></p>
                        <ul class="basket__items">
                            <?php foreach ($offers as $o): ?>
                                <li>
                                    <a href="<?= $h->e($h->url('/comparer/' . $o->productKey)) ?>"><?= $h->e($o->name) ?></a>
                                    <span class="muted small"><?= $h->e($o->brand) ?> · <?= $h->e($o->packLabel) ?><?= $o->mechanic ? ' · ' . $h->e($o->mechanic) : '' ?></span>
                                    <strong><?= $h->price($o->price) ?></strong>
                                    <button type="button" class="btn btn--icon btn--sm" data-remove="<?= $h->e($o->productKey) ?>" aria-label="Retirer <?= $h->e($o->name) ?>">✕</button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="section">
            <h2 class="section__title">Classement des enseignes pour votre liste</h2>
            <ol class="ranking">
                <?php foreach ($plan->stores as $i => $s): ?>
                    <li class="<?= $i === 0 ? 'is-best' : '' ?>">
                        <?= $h->storeBadge($s['store']) ?>
                        <span class="ranking__found"><?= $s['found'] ?>/<?= $available ?> produits en promo</span>
                        <strong><?= $h->price($s['total']) ?></strong>
                    </li>
                <?php endforeach; ?>
            </ol>
        </section>
    <?php endif; ?>

    <?php if ($plan->missing()): ?>
        <section class="section">
            <h2 class="section__title">Sans promo dans votre zone en ce moment</h2>
            <ul class="basket__missing">
                <?php foreach ($plan->missing() as $key): ?>
                    <li>
                        <span data-list-name="<?= $h->e($key) ?>"><?= $h->e($h->humanizeKey($key)) ?></span>
                        <button type="button" class="btn btn--icon btn--sm" data-remove="<?= $h->e($key) ?>" aria-label="Retirer">✕</button>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <p class="basket__footer">
        <button type="button" class="btn btn--ghost" data-list-clear>🗑️ Vider ma liste</button>
    </p>
</div>
