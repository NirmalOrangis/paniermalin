# 🧺 PanierMalin

Comparateur des promotions des supermarchés français : Carrefour, Auchan, E.Leclerc, Intermarché, Lidl, Aldi, Super U,
Monoprix, Franprix, Netto, Grand Frais, Picard.

- **Promos par zone** : toute la France, une région ou un département (Île-de-France par défaut).
- **Explorateur** avec filtres instantanés (enseignes, catégories, remise minimum, prix max, offres carte de fidélité) et tris
  (pertinence, remise, prix, prix au kg/L, fin de l'offre).
- **Comparateur** : le même produit dans toutes les enseignes, du moins cher au plus cher, avec l'économie réalisée.
- **Liste de courses** : calcule le magasin unique le moins cher et la meilleure combinaison d'enseignes.
- **Personnalisation sans compte** : après accord explicite (bandeau cookies), la zone, les enseignes préférées et les
  centres d'intérêt sont mémorisés dans le navigateur ; la page d'accueil affiche alors « Pour vous » et vos enseignes.
- **Rapide** : rendu serveur, 1 fichier CSS + 1 fichier JS (≈ 30 Ko non compressés), aucune police ni image externe,
  aucune librairie. Fonctionne même sans JavaScript.
- **Responsive** : navigation en barre du bas sur mobile, tiroir de filtres sur tablette, barre latérale sur desktop, mode sombre automatique.

> ⚠️ Les promotions fournies sont des **données de démonstration** générées (`bin/generate-demo-data.php`).
> Voir la section [Données](#données) pour brancher de vraies sources.

## Démarrer en local

Avec PHP 8.2+ installé :

```bash
php -S localhost:8000 -t public public/index.php
```

Ou avec Docker (aucune installation de PHP nécessaire) :

```bash
docker compose up
```

puis ouvrez http://localhost:8080.

Tests : `php tests/run.php` (ou `docker compose run --rm web php tests/run.php`).

## Architecture

PHP 8.2+ sans framework ni dépendance, organisé en couches (inspiré de Symfony / architecture hexagonale) pour pouvoir
migrer vers Symfony plus tard sans réécrire le métier.

```
public/            Racine web : front controller, CSS, JS, .htaccess
src/
  Kernel.php       Assemblage des services (injection de dépendances manuelle) et routes
  Http/            Request, Response, Router
  Controller/      Home, Promotion (explorateur), Compare, Page, Api (JSON)
  Domain/
    Model/         Promotion, Store, Category, Location, ProductOffers, BasketPlan
    Repository/    Interfaces des sources de données
    Search/        PromotionCriteria (filtres immuables), SearchResult
  Service/         Recherche, comparaison de prix, optimisation du panier, zones
  Preferences/     Lecture des cookies de préférences (avec consentement)
  Infrastructure/  Implémentations JSON des repositories
  View/            Moteur de templates PHP + helpers
templates/         Pages et partials (les mêmes cartes servent au rendu serveur et à l'API)
data/              Enseignes, catégories, régions, promotions (JSON)
bin/               Générateur de données de démonstration
deploy/            Configuration Nginx et guide de mise en ligne
tests/             Tests sans dépendance
```

### Routes

| URL | Rôle |
| --- | --- |
| `/` | Accueil personnalisé |
| `/promos` | Explorateur (`q`, `stores`, `cat`, `sort`, `remise`, `prixmax`, `loyal`, `loc`, `page`) |
| `/comparer`, `/comparer/{produit}` | Comparateur |
| `/liste` | Liste de courses |
| `/api/promotions`, `/api/suggest`, `/api/basket` | API JSON (HTML pré-rendu + données brutes) |

### Données personnelles

Aucune base de données, aucun compte. Tant que le visiteur n'a pas accepté le bandeau, rien n'est mémorisé
(la zone voyage dans l'URL, la liste reste en `sessionStorage`). Après accord : cookie `pm_prefs` (≤ 1 Ko, 6 mois)
et liste en `localStorage`. Boutons « Gérer les cookies » et « Effacer mes données » dans le pied de page.

## Données

Plan complet : [docs/DATA-SOURCES.md](docs/DATA-SOURCES.md). Le site fusionne plusieurs sources
(`CompositePromotionRepository`, réglées dans `config/app.php`) :

| Source | Fichier | Mise à jour |
| --- | --- | --- |
| Démonstration | `data/promotions.json` | `php bin/generate-demo-data.php` — désactivable avec `demo_data => false` |
| Saisie manuelle | `data/sources/manual.csv` | À la main (Excel / LibreOffice, séparateur `;`). Mode d'emploi et exemples : `data/sources/manual.example.csv` |
| Open Prices | `data/sources/open-prices.json` (non versionné) | `php bin/import-open-prices.php`, une fois par jour (cron) |

Après chaque modification du CSV, lancez `php bin/check-sources.php` : il affiche le nombre de promos par source et
la liste des lignes refusées avec la raison (enseigne inconnue, prix incohérent, date invalide…). Une ligne erronée
est ignorée, elle ne casse jamais le site.

Les prix Open Prices sont des **relevés en magasin** sans date de fin : ils s'affichent « 📸 Relevé le … » avec le
magasin, pendant `open_prices.validity_days` jours (7 par défaut). Ils sont sous licence ODbL : l'attribution est
dans le pied de page. Volume constaté le 28/10/2026 : seulement quelques promos françaises par semaine, d'où la
saisie manuelle en complément.

Les produits sont identifiés par leur **code-barres EAN** quand il est connu (Open Prices, saisie manuelle), ce qui
permet de comparer le même produit entre sources et enseignes.

Toute nouvelle source doit simplement implémenter `App\Domain\Repository\PromotionRepositoryInterface`
(ou produire un JSON au format ci-dessous avec des dates absolues `startsAt` / `endsAt`).

```json
{"id": "lidl-nutella-750g", "product": "nutella-750g", "name": "Pâte à tartiner", "brand": "Nutella",
 "category": "epicerie-sucree", "store": "lidl", "price": 3.84, "regularPrice": 5.12, "quantity": 0.75, "unit": "kg",
 "pack": "750 g", "mechanic": "2e à -50 %", "minQuantity": 2, "loyaltyCard": false,
 "startsAt": "2026-10-28", "endsAt": "2026-11-03", "zones": ["FR"], "source": "manual", "place": null}
```

## Mise en ligne

Voir [deploy/DEPLOY.md](deploy/DEPLOY.md).
