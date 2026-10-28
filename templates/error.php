<?php
/**
 * @var App\View\ViewHelpers $h
 * @var string               $title
 */
?>
<div class="empty empty--page">
    <p class="empty__icon" aria-hidden="true">🛒💨</p>
    <h1 class="page-title"><?= $h->e($title) ?></h1>
    <p class="muted">Cette page n'existe pas ou la promotion est terminée.</p>
    <p>
        <a class="btn btn--primary" href="<?= $h->e($h->url('/')) ?>">Retour à l'accueil</a>
        <a class="btn btn--ghost" href="<?= $h->e($h->url('/promos')) ?>">Toutes les promos</a>
    </p>
</div>
