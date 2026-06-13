# CvAnalyzer

Application web de gestion et d’analyse de CV (Laravel, Inertia, Vue 3, PostgreSQL).

Les candidats déposent leur dossier ; les RH filtrent et analysent les CV par mots-clés ; le super administrateur gère les gérants ; chaque gérant supervise son entreprise et son équipe RH.

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

| Rôle métier | `users.role` | Email | Connexion |
|-------------|--------------|-------|-----------|
| Super administrateur plateforme | `super_admin` | `admin@cvapp.test` | URL secrète `SUPER_ADMIN_LOGIN_PATH` (défaut : `/acces-admin-plateforme`) |
| Gérant entreprise | `admin` | `gerant@techcorp.test` | URL secrète `GERANT_LOGIN_PATH` (défaut : `/acces-gerant-entreprise`) |
| RH (sous-admin) | `sous_admin` | `rh@cvapp.test` | `/login` |
| Candidat | `candidat` | `candidat@cvapp.test` | `/login` |
