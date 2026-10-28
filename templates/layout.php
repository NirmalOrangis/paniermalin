<?php
/**
 * @var App\View\ViewHelpers $h
 * @var string $content
 */
$appName = (string) $h->config('name');
$pageTitle = isset($title) ? $title . ' · ' . $appName : $appName . ' · Les promos des supermarchés comparées';
$pageId ??= '';
$nav = [
    'home' => ['/', '🏠', 'Accueil'],
    'promos' => ['/promos', '🏷️', 'Promos'],
    'compare' => ['/comparer', '⚖️', 'Comparer'],
    'list' => ['/liste', '📝', 'Ma liste'],
];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= $h->e($pageTitle) ?></title>
    <meta name="description" content="<?= $h->e($description ?? $h->config('tagline')) ?>">
    <meta name="theme-color" content="#0f7b5f">
    <meta property="og:title" content="<?= $h->e($pageTitle) ?>">
    <meta property="og:description" content="<?= $h->e($description ?? $h->config('tagline')) ?>">
    <meta property="og:type" content="website">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="stylesheet" href="<?= $h->asset('assets/css/app.css') ?>">
    <script src="<?= $h->asset('assets/js/app.js') ?>" defer></script>
</head>
<body data-page="<?= $h->e($pageId) ?>" data-location="<?= $h->e($h->locationCode()) ?>">
<a class="skip-link" href="#main">Aller au contenu</a>

<header class="site-header">
    <div class="container site-header__inner">
        <a class="logo" href="<?= $h->e($h->url('/')) ?>" aria-label="<?= $h->e($appName) ?> — accueil">
            <span class="logo__mark" aria-hidden="true">🧺</span>
            <span class="logo__text">Panier<b>Malin</b></span>
        </a>

        <form class="search" action="/promos" method="get" role="search" data-search>
            <input type="hidden" name="loc" value="<?= $h->e($h->locationCode()) ?>">
            <label class="visually-hidden" for="q">Rechercher un produit, une marque…</label>
            <input id="q" name="q" type="search" placeholder="Nutella, couches, TV 55&quot;…" autocomplete="off"
                   value="<?= $h->e($query ?? '') ?>" role="combobox" aria-autocomplete="list" aria-controls="suggest" aria-expanded="false" maxlength="80">
            <button type="submit" class="search__btn" aria-label="Rechercher">
                <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M10 2a8 8 0 0 1 6.32 12.9l5.39 5.4-1.42 1.4-5.39-5.38A8 8 0 1 1 10 2Zm0 2a6 6 0 1 0 0 12 6 6 0 0 0 0-12Z"/></svg>
            </button>
            <div class="suggest" id="suggest" role="listbox" hidden></div>
        </form>

        <form class="location" method="get" data-location-form>
            <label for="loc" class="location__label"><span aria-hidden="true">📍</span><span class="visually-hidden">Votre zone</span></label>
            <select id="loc" name="loc" data-location-select>
                <option value="FR"<?= $h->locationCode() === 'FR' ? ' selected' : '' ?>>Toute la France</option>
                <?php foreach ($h->locationTree() as $node): ?>
                    <optgroup label="<?= $h->e($node['region']->name) ?>">
                        <option value="<?= $h->e($node['region']->code) ?>"<?= $h->locationCode() === $node['region']->code ? ' selected' : '' ?>><?= $h->e($node['region']->name) ?> · toute la région</option>
                        <?php foreach ($node['departments'] as $dep): ?>
                            <option value="<?= $h->e($dep->code) ?>"<?= $h->locationCode() === $dep->code ? ' selected' : '' ?>><?= $h->e($dep->label()) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
            <noscript><button type="submit" class="btn btn--sm">OK</button></noscript>
        </form>

        <nav class="nav" aria-label="Navigation principale">
            <?php foreach ($nav as $id => [$href, $icon, $label]): ?>
                <a class="nav__link<?= $pageId === $id ? ' is-active' : '' ?>" href="<?= $h->e($h->url($href)) ?>"<?= $pageId === $id ? ' aria-current="page"' : '' ?>>
                    <span class="nav__icon" aria-hidden="true"><?= $icon ?></span>
                    <span class="nav__label"><?= $h->e($label) ?></span>
                    <?php if ($id === 'list'): ?><span class="nav__badge" data-list-count hidden>0</span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
</header>

<main id="main" class="container main">
    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="container site-footer__inner">
        <p><strong><?= $h->e($appName) ?></strong> — <?= $h->e($h->config('tagline')) ?></p>
        <?php if ($h->config('demo_data')): ?>
            <p class="site-footer__notice">⚠️ Version de démonstration : les promotions affichées sont des exemples générés, pas des offres réelles. Prix indicatifs, vérifiez toujours en magasin.</p>
        <?php endif; ?>
        <p class="site-footer__links">
            <a href="<?= $h->e($h->url('/confidentialite')) ?>">Confidentialité</a>
            <a href="#" data-consent-open>Gérer les cookies</a>
            <a href="#" data-forget>Effacer mes données</a>
            <a href="/sitemap.xml">Plan du site</a>
        </p>
        <p class="site-footer__small">📸 Une partie des prix provient d'<a href="https://prices.openfoodfacts.org/" rel="noopener">Open Prices</a> (Open Food Facts), base collaborative sous licence <a href="https://opendatacommons.org/licenses/odbl/1-0/" rel="noopener">ODbL</a>.</p>
        <p class="site-footer__small">Les noms d'enseignes et de marques appartiennent à leurs propriétaires respectifs. <?= $h->e($appName) ?> n'est affilié à aucune enseigne.</p>
    </div>
</footer>

<div class="consent" data-consent role="dialog" aria-modal="false" aria-labelledby="consent-title" hidden>
    <p class="consent__title" id="consent-title">🍪 On retient vos préférences ?</p>
    <p class="consent__text">
        Avec votre accord, nous enregistrons <strong>dans votre navigateur uniquement</strong> votre zone, vos enseignes préférées,
        les catégories qui vous intéressent et votre liste de courses, pour vous afficher directement les bonnes promos à votre retour.
        Aucun compte, aucune publicité, aucun partage avec des tiers.
        <a href="/confidentialite">En savoir plus</a>
    </p>
    <div class="consent__actions">
        <button type="button" class="btn btn--ghost" data-consent-choice="refuse">Refuser</button>
        <button type="button" class="btn btn--primary" data-consent-choice="accept">Accepter</button>
    </div>
</div>

<div class="toast" data-toast role="status" aria-live="polite" hidden></div>
</body>
</html>
