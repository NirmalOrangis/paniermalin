<?php
/** @var App\View\ViewHelpers $h */
?>
<article class="prose">
    <h1 class="page-title">Confidentialité et cookies</h1>
    <p><?= $h->e($h->config('name')) ?> fonctionne <strong>sans compte et sans base de données d'utilisateurs</strong>. Nous ne collectons aucune donnée personnelle sur nos serveurs, n'utilisons aucun outil de mesure d'audience tiers et n'affichons aucune publicité ciblée.</p>

    <h2>Ce qui est enregistré, et seulement si vous l'acceptez</h2>
    <p>Lors de votre première visite, un bandeau vous demande votre accord. Si vous acceptez, les informations suivantes sont stockées <strong>dans votre navigateur</strong> pendant 6 mois maximum :</p>
    <table class="table">
        <thead><tr><th>Nom</th><th>Type</th><th>Contenu</th><th>Durée</th></tr></thead>
        <tbody>
            <tr><td><code>pm_consent</code></td><td>Cookie</td><td>Votre choix (accepté ou refusé). Nécessaire pour ne pas vous redemander à chaque page.</td><td>6 mois</td></tr>
            <tr><td><code>pm_prefs</code></td><td>Cookie</td><td>Zone choisie, enseignes filtrées, offres carte de fidélité oui/non, catégories qui vous intéressent (compteurs).</td><td>6 mois</td></tr>
            <tr><td><code>pm_list</code></td><td>Stockage local</td><td>Votre liste de courses (identifiants de produits).</td><td>Jusqu'à suppression</td></tr>
        </tbody>
    </table>
    <p>Le cookie <code>pm_prefs</code> est lu par notre serveur uniquement pour afficher une page d'accueil personnalisée ; il n'est ni conservé, ni journalisé, ni transmis à quiconque.</p>

    <h2>Si vous refusez</h2>
    <p>Le site reste entièrement utilisable. Votre zone est simplement conservée dans l'adresse des pages et votre liste de courses le temps de votre visite.</p>

    <h2>Changer d'avis</h2>
    <p>
        <button type="button" class="btn btn--ghost" data-consent-open>Modifier mon choix</button>
        <button type="button" class="btn btn--ghost" data-forget>Effacer toutes mes données</button>
    </p>

    <h2>Les prix affichés</h2>
    <p>Les prix et promotions sont fournis à titre indicatif. Seuls les prix affichés en magasin ou sur le site de l'enseigne font foi.</p>
</article>
