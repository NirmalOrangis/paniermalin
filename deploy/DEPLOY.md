# Mise en ligne de PanierMalin

Le site est du PHP 8.2+ sans base de données ni dépendance : n'importe quel hébergement PHP convient.
Seule règle : **la racine web du domaine doit pointer sur le dossier `public/`** (le reste du projet ne doit pas être accessible).

## 1. Choisir un nom de domaine (≈ 7 à 15 €/an)

Registrars français recommandés : **OVHcloud**, **Gandi**, **Infomaniak**. Un `.fr` est idéal pour un public français
(ex. `paniermalin.fr`, `promos-malines.fr` — vérifiez la disponibilité et l'absence de marque déposée sur le site de l'INPI).

## 2. Choisir l'hébergement

| Option | Prix indicatif | Pour qui |
| --- | --- | --- |
| **Mutualisé** OVHcloud Perso/Pro, o2switch, Infomaniak | 3 à 8 €/mois | Le plus simple : upload FTP/Git, SSL gratuit, rien à administrer. Parfait pour démarrer. |
| **VPS** Hetzner CX22, OVHcloud VPS, Scaleway | 4 à 8 €/mois | Plus de contrôle (cron d'import de données, Docker…). Demande un peu d'administration Linux. |
| **Conteneur** (Docker) sur un VPS ou un PaaS | variable | Le `Dockerfile` fourni est prêt à l'emploi. |

Recommandation : **domaine `.fr` + VPS Hetzner CX22 (ou OVHcloud VPS)** si vous comptez ajouter un import automatique des
promotions plus tard (tâches planifiées) ; sinon un **mutualisé OVHcloud Perso** suffit largement pour lancer le site.

## 3a. Déploiement sur un hébergement mutualisé (Apache)

1. Dans le panel de l'hébergeur, choisissez **PHP 8.3** et réglez la *racine du site / dossier racine* sur `public`.
2. Envoyez tout le projet (FTP, ou `git clone` si l'hébergeur propose SSH/Git).
3. Le fichier `public/.htaccess` gère la réécriture d'URL, la compression et le cache.
4. Activez le certificat SSL gratuit (Let's Encrypt) dans le panel, puis décommentez la redirection HTTPS dans `public/.htaccess`.
5. Optionnel : définissez la variable `APP_URL=https://votre-domaine.fr` (pour des URLs absolues correctes dans le sitemap).

## 3b. Déploiement sur un VPS (Nginx + PHP-FPM, Ubuntu 24.04)

```bash
apt update && apt install -y nginx php8.3-fpm php8.3-mbstring php8.3-opcache git certbot python3-certbot-nginx
git clone https://github.com/<vous>/<depot>.git /var/www/paniermalin
cp /var/www/paniermalin/deploy/nginx.conf /etc/nginx/sites-available/paniermalin   # puis éditez le domaine
ln -s /etc/nginx/sites-available/paniermalin /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
certbot --nginx -d votre-domaine.fr -d www.votre-domaine.fr
```

Mise à jour ensuite : `cd /var/www/paniermalin && git pull` (et `systemctl reload php8.3-fpm` si OPcache garde l'ancienne version).

## 3c. Déploiement avec Docker

```bash
docker build -t paniermalin .
docker run -d --restart unless-stopped -p 80:80 -e APP_URL=https://votre-domaine.fr paniermalin
```

(Placez un reverse proxy Caddy ou Traefik devant pour le HTTPS automatique.)

## 4. Import quotidien des prix Open Prices

Le script `bin/import-open-prices.php` doit tourner une fois par jour (il conserve l'ancien fichier en cas d'erreur réseau).

- **VPS** : `crontab -e -u www-data` puis ajoutez
  `15 6 * * * php /var/www/paniermalin/bin/import-open-prices.php >> /var/log/paniermalin-import.log 2>&1`
- **Mutualisé** (OVHcloud « Tâches planifiées – Cron », o2switch « Tâches Cron ») : créez une tâche quotidienne
  qui exécute `bin/import-open-prices.php` avec PHP 8.3.
- **Docker** : `docker exec <conteneur> php bin/import-open-prices.php` depuis le cron de l'hôte.

Le dossier `data/sources/` doit être accessible en écriture par l'utilisateur qui exécute la tâche.

## 5. DNS

Chez le registrar, créez un enregistrement **A** (et **AAAA** si IPv6) pour `@` et `www` vers l'adresse IP du serveur.
La propagation prend de quelques minutes à quelques heures.

## 6. Avant d'ouvrir au public

- Remplir `data/sources/manual.csv` pour votre zone de lancement (voir le README, section « Données »).
- Passer `demo_data` à `false` dans `config/app.php` une fois les vraies données branchées.
- Ajouter des mentions légales (éditeur du site, hébergeur) : obligatoires en France (LCEN).
