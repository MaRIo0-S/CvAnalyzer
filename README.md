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
