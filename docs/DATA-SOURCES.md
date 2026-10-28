# Sources de données réelles — plan

*Étude du 28/10/2026. Les chiffres Open Prices ont été mesurés via son API publique le même jour.*

## Constat

Aucune enseigne française (Carrefour, Lidl, Auchan, Leclerc…) ne publie d'API ouverte de ses promotions.
Les promos vivent dans des catalogues (papier, PDF, applis) et sur les sites drive. Il n'existe donc pas de « bouton magique » :
il faut **combiner plusieurs sources** et faire monter la couverture progressivement.

## Les options

| Source | Coût | Couverture promos France | Légalité / risque | Verdict |
| --- | --- | --- | --- | --- |
| **Saisie manuelle** depuis les catalogues (quelques magasins, une zone) | Temps (~2-3 h/semaine) | Ce que vous saisissez | OK : saisir des prix isolés (des faits) est permis. Ne pas copier les images/visuels des catalogues ni reprendre un catalogue entier de façon systématique. | ✅ Démarrage |
| **Open Prices** (Open Food Facts) — API publique, données ouvertes | Gratuit | Faible : **~196 prix remisés en France sur les 30 derniers jours**, dans 24 villes (Super U, Monoprix, Carrefour Market, Leclerc, Auchan…) | OK sous licence **ODbL** : citer Open Prices et repartager les données modifiées sous la même licence | ✅ Complément + prix de référence |
| **Contribution des visiteurs** (photo d'étiquette / ticket envoyée à Open Prices) | Gratuit | Grandit avec l'audience | OK (les visiteurs contribuent à une base ouverte) | ✅ Moyen terme |
| **Flux d'affiliation** (E.Leclerc sur Awin, Auchan via TimeOne, etc.) | Gratuit + **commissions** | Surtout non-alimentaire en ligne (high-tech, électroménager) | OK, contrat officiel. Nécessite un site en ligne et un statut (micro-entreprise suffit) | ✅ Dès la mise en ligne |
| **Partenariat agrégateurs de catalogues** (Bonial, ShopFully/Tiendeo) | Négocié | Excellente (tous les catalogues) | OK si contrat | ⏳ Quand il y a du trafic |
| **Données de veille promo payantes** (panélistes) | Élevé (milliers d'€) | Excellente | OK | ❌ Pas au début |
| **Scraping** des sites drive / catalogues | Gratuit en apparence | Bonne | ⚠️ CGU interdisant l'extraction, **droit sui generis du producteur de base de données**, protections anti-bot, jurisprudence incertaine | ❌ À éviter |

## Recommandation : 3 phases

### Phase 1 — Lancement local crédible (0 €, maintenant) — ✅ code en place

Implémenté : `data/sources/manual.csv` (+ `manual.example.csv`), `bin/import-open-prices.php`, `bin/check-sources.php`,
fusion des sources, affichage « 📸 Relevé le … », attribution ODbL, codes-barres EAN comme identifiants, liste complète
des 101 départements. Premier import réel (28/10/2026) : 59 prix remisés en euros sur 7 jours dans le monde,
8 en France, **4 retenus** — la saisie manuelle reste indispensable.

1. **Réduire la zone** au lancement : l'Essonne (ou là où vous faites vos courses), 5 à 8 magasins.
2. **Saisie manuelle hebdomadaire** des meilleures promos de leurs catalogues dans un fichier CSV simple (`data/sources/manual.csv`).
3. **Import Open Prices** quotidien en complément (prix remisés en France, affichés comme « relevé le … à … »).
4. Afficher l'attribution « Données en partie issues d'Open Prices (ODbL) ».

### Phase 2 — Mise en ligne et revenus
5. Inscription aux programmes d'affiliation (E.Leclerc via Awin, Auchan via TimeOne, puis d'autres) pour les rayons high-tech / électroménager : flux produits officiels avec prix, et commission sur les ventes.
6. Bouton « Signaler une promo » qui envoie vers Open Prices (photo d'étiquette) : chaque visiteur enrichit la base.

### Phase 3 — Croissance
7. Avec quelques milliers de visiteurs par mois, contacter Bonial et ShopFully, puis directement les enseignes (offre : trafic « drive-to-store » mesurable).

## Changements techniques à prévoir (Phase 1)

- **Identifiant produit = code-barres EAN** quand il est connu (c'est la clé d'Open Prices et d'Open Food Facts : noms, marques et photos gratuits). `productKey` reste un slug pour les produits frais sans EAN.
- **`CompositePromotionRepository`** qui fusionne plusieurs sources (manuel, Open Prices, affiliation), chacune derrière `PromotionRepositoryInterface`. Ajout d'un champ `source` sur `Promotion`.
- **Importers en tâche planifiée** (`bin/import-*.php`, cron quotidien) qui écrivent des fichiers JSON dans `data/sources/` : le site reste sans base de données et ultra-rapide, l'API externe n'est jamais appelée pendant la navigation.
- **Normalisation** : nom de magasin OSM → enseigne (« Hypermarché E.Leclerc », « E. Leclerc » → `leclerc`) ; code postal → département (2 premiers chiffres, cas Corse 2A/2B et DOM 97x).
- **Dates** : Open Prices ne donne pas de date de fin de promo. On considère un prix remisé valable 7 jours après le relevé et on l'affiche honnêtement (« Prix relevé le 26 oct. »), à distinguer des offres saisies avec date de fin.

## Sources

- [Open Prices — jeu de données sur data.gouv.fr](https://www.data.gouv.fr/datasets/open-prices) (ODbL, mise à jour quotidienne)
- [Open Prices — wiki Open Food Facts](https://wiki.openfoodfacts.org/Prices) (API, sources des données)
- [Programme d'affiliation E.Leclerc sur Awin](https://ui.awin.com/merchant-profile/15135)
- [Programme d'affiliation Auchan (affi.io)](https://affi.io/m/auchan)
- [Tiendeo devient ShopFully en France](https://fr.themedialeader.com/?p=52745)
- [Scraping et droit du producteur de base de données](https://consultation.avocat.fr/blog/tanguy-allain/article-41711-web-scraping-attention-aux-droits-du-producteur-de-base-de-donnees.html)
- [Scraping d'annonces : la justice divisée](https://monimmeuble.com/actualite/scraping-dannonces-immobilieres-la-justice-divise-la-french-tech)
- [Protéger un site contre le scraping illicite (Hogan Lovells)](https://www.engage.hoganlovells.com/knowledgeservices/insights-and-analysis/france-protecting-a-website-from-unlawful-data-scraping)
