# CvAnalyzer

Application web de gestion et d’analyse de CV (Laravel, Inertia, Vue 3, PostgreSQL).

Les candidats déposent leur dossier ; les RH filtrent et analysent les CV par mots-clés ; l’administrateur gère les comptes RH.

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm install && npm run build
php artisan serve
```

Comptes de démonstration : voir `database/seeders/DemoDataSeeder.php` (mot de passe `password`).

| Rôle | Email | Connexion |
|------|-------|-----------|
| Admin plateforme | `admin@cvapp.test` | URL secrète `ADMIN_LOGIN_PATH` (défaut : `/acces-admin-plateforme`) |
| Gérant (super-admin) | `gerant@techcorp.test` | URL secrète `GERANT_LOGIN_PATH` (défaut : `/acces-gerant-entreprise`) |
| RH | `rh@cvapp.test` | `/login` |
| Candidat | `candidat@cvapp.test` | `/login` |

Les chemins admin et gérant ne sont **pas** affichés sur le site public. Définissez-les dans `.env` :

```env
ADMIN_LOGIN_PATH=votre-chemin-admin-secret
GERANT_LOGIN_PATH=votre-chemin-gerant-secret
```

## Messages de contact (landing)

Le formulaire de la page d'accueil (`POST /contact`) enregistre chaque demande dans la table **`messages_contact`** (PostgreSQL).

L'administrateur les consulte dans **Admin → Messages** (`/admin/messages-contact`) ou via les compteurs du back-office.

Après `git pull`, exécuter `php artisan migrate` si une nouvelle migration est présente.

Table **`poste_mot_cle`** : pivot obligatoire entre `postes` et `mots_cles` (analyse RH) — ne pas supprimer.

## Déploiement Railway (Docker)

1. Créer un projet sur [Railway](https://railway.com), connecter ce dépôt Git.
2. Ajouter un service **PostgreSQL**, puis lier les variables à l’app web.
3. Variables obligatoires sur le service web :

| Variable | Valeur |
|----------|--------|
| `APP_KEY` | `php artisan key:generate --show` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | URL Railway (`https://….up.railway.app`) |
| `DB_CONNECTION` | `pgsql` |
| `DATABASE_URL` | fournie par le plugin Postgres |
| `SESSION_DRIVER` | `database` |
| `SESSION_SECURE_COOKIE` | `true` |
| `CACHE_STORE` | `database` |

4. Railway détecte `Dockerfile` + `railway.toml` : build Vite + PHP 8.3 / Nginx, migrations au démarrage, healthcheck `/up`.

**Fichiers CV** : stockés dans `storage/app/public`. Sur Railway le disque est éphémère — pour la production, monter un [Volume](https://docs.railway.com/guides/volumes) sur `/var/www/html/storage/app` ou utiliser S3 plus tard.

Test local :

```bash
docker build -t cvapp .
docker run --rm -p 8080:8080 -e PORT=8080 -e APP_KEY=base64:... -e DATABASE_URL=... cvapp
```

## Apprendre le code (local)

Guide complet pour comprendre et réécrire le projet : **`docs/apprendre/README.md`** (parcours, syntaxe, chaque fichier, exercices).
