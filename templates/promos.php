<?php
/**
 * @var App\View\ViewHelpers                $h
 * @var App\Domain\Search\PromotionCriteria $criteria
 * @var App\Domain\Search\SearchResult      $result
 * @var array<string, int>                  $offerCounts
 * @var string                              $title
 */
$activeFilters = count($criteria->stores) + count($criteria->categories) + ($criteria->minDiscount ? 1 : 0) + ($criteria->maxPrice !== null ? 1 : 0) + ($criteria->includeLoyalty ? 0 : 1);
$items = $result->items;
?>
<div class="explorer" data-explorer>
    <div class="explorer__head">
        <div>
            <h1 class="page-title"><?= $h->e($title) ?></h1>
            <p class="muted"><span data-total><?= $h->plural($result->total, 'promotion', 'promotions') ?></span> · <?= $h->e($h->location()?->label() ?? '') ?></p>
        </div>
        <div class="explorer__tools">
            <button type="button" class="btn btn--ghost filters-toggle" data-filters-open aria-controls="filters" aria-expanded="false">
                ⚙️ Filtres <span class="filters-toggle__count" data-filters-count<?= $activeFilters ? '' : ' hidden' ?>><?= $activeFilters ?></span>
            </button>
            <label class="visually-hidden" for="sort">Trier par</label>
            <select id="sort" name="sort" form="filters" class="select">
                <?php foreach (App\Domain\Search\PromotionCriteria::SORTS as $value => $label): ?>
                    <option value="<?= $h->e($value) ?>"<?= $criteria->sort === $value ? ' selected' : '' ?>><?= $h->e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="explorer__body">
        <form id="filters" class="filters" action="/promos" method="get" data-filters aria-label="Filtres">
            <div class="filters__head">
                <strong>Filtres</strong>
                <button type="button" class="btn btn--icon" data-filters-close aria-label="Fermer les filtres">✕</button>
            </div>
            <input type="hidden" name="f" value="1">
            <input type="hidden" name="loc" value="<?= $h->e($criteria->location) ?>">

            <div class="field">
                <label for="filter-q" class="field__label">Mot-clé</label>
                <input id="filter-q" class="input" type="search" name="q" value="<?= $h->e($criteria->query) ?>" placeholder="ex. café, lessive…" maxlength="80">
            </div>

            <fieldset class="field">
                <legend class="field__label">Enseignes <button type="button" class="link-btn" data-check-all="stores[]">tout / rien</button></legend>
                <div class="checks">
                    <?php foreach ($h->stores() as $store): ?>
                        <?php $count = $result->storeFacets[$store->id] ?? 0; ?>
                        <label class="check<?= $count === 0 ? ' is-zero' : '' ?>" data-facet-row="store:<?= $h->e($store->id) ?>">
                            <input type="checkbox" name="stores[]" value="<?= $h->e($store->id) ?>"<?= in_array($store->id, $criteria->stores, true) ? ' checked' : '' ?>>
                            <span class="check__dot" style="--store-bg:<?= $h->e($store->color) ?>"></span>
                            <span class="check__label"><?= $h->e($store->name) ?></span>
                            <span class="check__count" data-facet="store:<?= $h->e($store->id) ?>"><?= $count ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <fieldset class="field">
                <legend class="field__label">Catégories</legend>
                <div class="checks">
                    <?php foreach ($h->categories() as $cat): ?>
                        <?php $count = $result->categoryFacets[$cat->id] ?? 0; ?>
                        <label class="check<?= $count === 0 ? ' is-zero' : '' ?>" data-facet-row="cat:<?= $h->e($cat->id) ?>">
                            <input type="checkbox" name="cat[]" value="<?= $h->e($cat->id) ?>"<?= in_array($cat->id, $criteria->categories, true) ? ' checked' : '' ?>>
                            <span class="check__emoji" aria-hidden="true"><?= $cat->emoji ?></span>
                            <span class="check__label"><?= $h->e($cat->name) ?></span>
                            <span class="check__count" data-facet="cat:<?= $h->e($cat->id) ?>"><?= $count ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <fieldset class="field">
                <legend class="field__label">Remise minimum</legend>
                <div class="segmented">
                    <?php foreach (App\Service\CriteriaFactory::DISCOUNT_STEPS as $step): ?>
                        <label>
                            <input type="radio" name="remise" value="<?= $step ?>"<?= $criteria->minDiscount === $step ? ' checked' : '' ?>>
                            <span><?= $step === 0 ? 'Toutes' : '−' . $step . '%' ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <div class="field">
                <label for="prixmax" class="field__label">Prix maximum (€)</label>
                <input id="prixmax" class="input" type="number" name="prixmax" min="0" step="0.5" inputmode="decimal" value="<?= $criteria->maxPrice !== null ? $h->e($criteria->maxPrice) : '' ?>" placeholder="Sans limite">
            </div>

            <label class="switch">
                <input type="checkbox" name="loyal" value="1"<?= $criteria->includeLoyalty ? ' checked' : '' ?>>
                <span>Inclure les offres avec carte de fidélité 💳</span>
            </label>

            <div class="filters__actions">
                <a class="btn btn--ghost" href="<?= $h->e($h->url('/promos', ['f' => '1', 'loyal' => '1'])) ?>" data-filters-reset>Réinitialiser</a>
                <button type="submit" class="btn btn--primary" data-filters-apply>Voir les résultats</button>
            </div>
        </form>
        <div class="filters-backdrop" data-filters-close hidden></div>

        <div class="results">
            <div class="grid" data-results aria-live="polite">
                <?php include __DIR__ . '/partials/cards.php'; ?>
            </div>
            <div class="empty" data-empty<?= $result->isEmpty() ? '' : ' hidden' ?>>
                <p class="empty__icon" aria-hidden="true">🔎</p>
                <p><strong>Aucune promo ne correspond à ces filtres.</strong></p>
                <p class="muted">Essayez d'élargir la zone, de retirer une enseigne ou un mot-clé.</p>
            </div>
            <p class="load-more">
                <a class="btn btn--ghost" data-load-more href="<?= $h->e($h->url('/promos', $criteria->with(['page' => $criteria->page + 1])->toQuery())) ?>"<?= $result->hasMore() ? '' : ' hidden' ?>>Voir plus de promos</a>
            </p>
        </div>
    </div>
</div>
