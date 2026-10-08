# SolarShare — Projet Applications Web Avancées 2026-2027 (5 TWIN)

Plateforme de **location et de partage d'équipements d'énergie renouvelable** entre particuliers
(panneaux solaires portables, batteries, mini-éoliennes, onduleurs).

**Périmètre conservé** : **Module 3 — Avis & signalements** + **Auth/Utilisateurs** (tâche commune).
Les modules 1 (catalogue), 2 (réservations & paiements) et 4 (incidents & maintenance) ont été retirés ;
les modèles `Equipment`, `Category` et `Reservation` restent en place comme dépendances de données des avis.

**Stack** : Laravel 12 · Blade · Eloquent ORM · SQLite (MySQL possible) · Bootstrap 5 · Chart.js · GitHub

---

## 1. Installation

Prérequis : PHP 8.2+, Composer.

```bash
bash setup.sh        # Linux / macOS / Git Bash
setup.bat            # Windows
cd solarshare
php artisan serve    # http://127.0.0.1:8000
```

Le script : `composer create-project laravel/laravel:^12` → copie le code SolarShare → `migrate:fresh --seed` → `storage:link`.

### Comptes de démonstration

| Rôle | E-mail | Mot de passe |
|---|---|---|
| Administrateur (back office `/admin`) | admin@solarshare.tn | Admin1234 |
| Utilisateur | sami@example.com | Password123 |
| Utilisateur | leila@example.com | Password123 |
| Utilisateur | karim@example.com | Password123 |

### Fonctionnalité IA (optionnel)

- Par défaut (`AI_DRIVER=none`) : **moteur local** (règles + statistiques) pour l'analyse d'avis. Aucune clé requise.
- Avec un LLM : renseigner `AI_DRIVER` (`anthropic` ou `openai`), `AI_API_KEY`, `AI_MODEL` dans `.env`.
- Compatible Ollama / Groq via `AI_DRIVER=openai` + `AI_BASE_URL`.
- Si l'API échoue, l'application bascule automatiquement sur le moteur local.

---

## 2. Tâches communes (toute l'équipe)

- **Authentification** : `Auth/AuthController`, vues `resources/views/auth/`, middleware `role`.
- **Templates Blade** : front office `layouts/front.blade.php` · back office `layouts/admin.blade.php`.
- **Composants de formulaire** : `resources/views/components/form/*`.
- **Module utilisateurs** : réservé (aucun étudiant ne le prend).
- **GitHub** : un dépôt, une branche par module (`feature/module-3-avis`…), pull requests obligatoires.

> Les templates sont réalisés en Bootstrap 5. Pour intégrer un template tiers, remplacer le contenu de
> `layouts/front.blade.php` et `layouts/admin.blade.php` en conservant `@yield('content')`, `@stack('scripts')` et le menu.

---

## 3. Module conservé — Avis & signalements

- **CRUD back office** : `Admin/ReviewController`, `Admin/ReviewReportController` (jointure `reviews ⟷ equipment ⟷ users`, `review_reports ⟷ reviews`).
- **Front office** : `Front/ReviewController` (avis après location terminée, signalement).
- **Formulaires avancés** : `ReviewRequest`, `ReviewReportRequest` (`required_if:reason,other`).
- **IA** : `Services/Ai/ReviewAnalyzer` — sentiment (positif/neutre/négatif, score −1…1) et modération automatique : un avis toxique ou contenant un lien est masqué en attente de décision.

### Modules retirés

- ~~Module 1 — Catalogue~~ (contrôleurs, vues et routes supprimés).
- ~~Module 2 — Réservations & paiements~~ (contrôleurs, vues et routes supprimés ; `Reservation` conservée comme dépendance des avis).
- ~~Module 4 — Incidents & maintenance~~ (contrôleurs, vues et routes supprimés).

---

## 4. Conformité au cahier des charges

| Exigence | Réalisation |
|---|---|
| Laravel 12 | Squelette officiel `^12.0` |
| 2 templates Blade (front + back) | `layouts/front`, `layouts/admin` |
| ORM pour la persistance | **Eloquent** (ORM de Laravel) — modèles + relations |
| Formulaires avancés + validation | `ReviewRequest`, `ReviewReportRequest`, composants Blade, règles métier |
| CRUD avec jointure | Avis et signalements, listes construites avec `join()` + `with()` |
| Fonctionnalité avancée IA | Analyse de sentiment + modération auto, avec repli local |
| Authentification | Inscription, connexion, rôles `admin` / `user` |
| Plateforme collaborative | GitHub + `.github/workflows/ci.yml` |

## 5. Tests

```bash
php artisan test
```

`tests/Feature/SolarShareTest.php` : pages publiques, pages des modules retirés (404), accès admin, modération d'avis, analyse de sentiment.

## 6. Pour aller plus loin

- Docker + SonarQube (diapo « Technologies DevOps »).
- Passage à MySQL : modifier `DB_*` dans `.env`.
- Policies Laravel pour remplacer les contrôles `abort_unless`.
