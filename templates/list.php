<?php
/** @var App\View\ViewHelpers $h */
?>
<section class="hero hero--compact">
    <h1 class="hero__title">📝 Ma liste de courses</h1>
    <p class="hero__text">Ajoutez des produits avec le bouton <span class="kbd">＋</span> : on calcule le magasin le moins cher pour tout votre panier, ou la meilleure combinaison d'enseignes.</p>
</section>

<p class="notice" data-list-session-note hidden>
    Sans votre accord pour les cookies, la liste est conservée uniquement le temps de cette visite.
    <a href="#" data-consent-open>Activer la mémorisation</a>
</p>

<div class="empty" data-list-empty>
    <p class="empty__icon" aria-hidden="true">🧺</p>
    <p><strong>Votre liste est vide.</strong></p>
    <p class="muted">Parcourez les promos et ajoutez les produits qui vous intéressent.</p>
    <p><a class="btn btn--primary" href="<?= $h->e($h->url('/promos')) ?>">Voir les promos</a></p>
</div>

<div data-list-root aria-live="polite"></div>
