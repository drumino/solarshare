# SolarShare — Module utilisateurs

Plateforme de **location et de partage d'équipements d'énergie renouvelable** entre particuliers.
Cette base contient le socle commun (authentification, rôles, templates) et le **module utilisateurs**.

**Stack** : Laravel 12 · Blade · Eloquent ORM · SQLite (MySQL possible) · Bootstrap 5 · Chart.js · GitHub

---

## 1. Installation

Prérequis : PHP 8.2+, Composer.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve    # http://127.0.0.1:8000
```

### Comptes de démonstration

| Rôle | E-mail | Mot de passe |
|---|---|---|
| Administrateur (back office `/admin`) | admin@solarshare.tn | Admin1234 |
| Utilisateur | sami@example.com | Password123 |
| Utilisateur | leila@example.com | Password123 |
| Utilisateur | karim@example.com | Password123 |

---

## 2. Socle commun

- **Authentification** : `Auth/AuthController` (inscription, connexion, déconnexion), vues `resources/views/auth/`.
- **Rôles** : colonne `users.role` (`admin` / `user`), middleware `role` (`Http/Middleware/EnsureRole`).
- **Templates Blade** : front office `layouts/front.blade.php` · back office `layouts/admin.blade.php`.
- **Composants de formulaire** : `resources/views/components/form/*` (`input`, `select`, `textarea`), `x-badge`, `partials/flash`, `partials/delete`.

> Pour intégrer un template tiers, remplacer le contenu de `layouts/front.blade.php` et `layouts/admin.blade.php`
> en conservant `@yield('content')`, `@stack('scripts')` et le menu.

## 3. Module utilisateurs

| | Fichiers |
|---|---|
| **Modèle** | `Models/User` — `ROLES`, `isAdmin()`, `isLastAdmin()` |
| **CRUD (back office)** | `Admin/UserController`, vues `admin/users/{index,form}` |
| **Profil (front office)** | `Front/ProfileController`, vue `front/profile/edit` |
| **Validation** | `Requests/UserRequest` |
| **Tableau de bord** | `Admin/DashboardController` — KPI, utilisateurs par rôle / ville (Chart.js), dernières inscriptions |

### Routes

| Méthode | URL | Nom |
|---|---|---|
| GET | `/admin/users` (recherche `q`, filtre `role`) | `admin.users.index` |
| GET / POST | `/admin/users/create`, `/admin/users` | `admin.users.create` / `store` |
| GET / PUT | `/admin/users/{user}/edit`, `/admin/users/{user}` | `admin.users.edit` / `update` |
| DELETE | `/admin/users/{user}` | `admin.users.destroy` |
| GET / PUT | `/mon-profil` | `front.profile.edit` / `update` |
| PUT | `/mon-profil/mot-de-passe` | `front.profile.password` |

### Règles métier et validation

- E-mail unique (ignoré pour l'utilisateur modifié), téléphone `^[0-9+\s]{8,15}$`, rôle `admin` ou `user`.
- Mot de passe : 8 caractères minimum, lettres et chiffres, confirmation obligatoire. Il est requis à la création ; laissé vide à la modification, il reste inchangé.
- Un administrateur ne peut pas supprimer son propre compte.
- Le dernier administrateur ne peut être ni supprimé ni rétrogradé (`UserRequest::after()`).
- Le changement de mot de passe depuis le profil exige le mot de passe actuel (`current_password`).

## 4. Tests

```bash
php artisan test
```

`tests/Feature/UserModuleTest.php` : pages publiques, inscription, accès admin, CRUD utilisateurs, unicité de l'e-mail,
protections du dernier administrateur, mise à jour du profil et du mot de passe.

## 5. Pour aller plus loin

- Docker + SonarQube.
- Passage à MySQL : modifier `DB_*` dans `.env`.
- Policies Laravel pour remplacer les contrôles dans les contrôleurs.
