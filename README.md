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

## Messages de contact (landing)

Le formulaire de la page d'accueil (`POST /contact`) enregistre chaque demande dans la table **`messages_contact`** (PostgreSQL).

L'administrateur les consulte dans **Admin → Messages** (`/admin/messages-contact`) ou via les compteurs du back-office.

Après `git pull`, exécuter `php artisan migrate` si une nouvelle migration est présente.

Table **`poste_mot_cle`** : pivot obligatoire entre `postes` et `mots_cles` (analyse RH) — ne pas supprimer.

## Apprendre le code (local)

Guide complet pour comprendre et réécrire le projet : **`docs/apprendre/README.md`** (parcours, syntaxe, chaque fichier, exercices).
