# SolarShare — Projet Applications Web Avancées 2026-2027 (5 TWIN)

Plateforme de **location et de partage d'équipements d'énergie renouvelable** entre particuliers
(panneaux solaires portables, batteries, mini-éoliennes, onduleurs).

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

### Fonctionnalités IA (optionnel)

- Par défaut (`AI_DRIVER=none`) : **moteur local** (règles + statistiques). Aucune clé requise.
- Avec un LLM : renseigner `AI_DRIVER` (`anthropic` ou `openai`), `AI_API_KEY`, `AI_MODEL` dans `.env`.
- Compatible Ollama / Groq via `AI_DRIVER=openai` + `AI_BASE_URL`.
- Si l'API échoue, l'application bascule automatiquement sur le moteur local.

---

## 2. Tâches communes (toute l'équipe)

- **Authentification** : `Auth/AuthController`, vues `resources/views/auth/`, middleware `role`.
- **Templates Blade** : front office `layouts/front.blade.php` · back office `layouts/admin.blade.php`.
- **Composants de formulaire** : `resources/views/components/form/*`.
- **Module utilisateurs** : réservé (aucun étudiant ne le prend).
- **GitHub** : un dépôt, une branche par module (`feature/module-1-catalogue`…), pull requests obligatoires.

> Les templates sont réalisés en Bootstrap 5. Pour intégrer un template tiers, remplacer le contenu de
> `layouts/front.blade.php` et `layouts/admin.blade.php` en conservant `@yield('content')`, `@stack('scripts')` et le menu.

---

## 3. Répartition des 4 modules

| | Membre 1 | Membre 2 | Membre 3 | Membre 4 |
|---|---|---|---|---|
| **Module** | Catalogue | Réservations & paiements | Avis & signalements | Incidents & maintenance |
| **Entité A** | `Category` | `Reservation` | `Review` | `Incident` |
| **Entité B** (jointure) | `Equipment` | `Payment` | `ReviewReport` | `MaintenanceTask` |
| **IA** | Annonce générée + prix suggéré | Assistant de dimensionnement énergétique | Analyse de sentiment + modération auto | Triage d'incident + conseil d'intervention |

### Module 1 — Catalogue (Membre 1)

- **CRUD** : `Admin/CategoryController`, `Admin/EquipmentController` (jointure `equipment ⟷ categories ⟷ users`).
- **Front** : `Front/EquipmentController` (catalogue filtré/trié, fiche, proposition d'annonce).
- **Formulaires avancés** : `EquipmentRequest` — champs conditionnels (puissance requise si panneau/éolien/onduleur, capacité si batterie), upload image (type, taille, dimensions), `Rule::requiredIf`.
- **IA** : `Services/Ai/EquipmentAssistant` — `describe()` (LLM) et `suggestPrice()` (formule technique + moyenne du marché). Boutons AJAX dans `public/js/equipment-form.js`.

### Module 2 — Réservations & paiements (Membre 2)

- **CRUD** : `Admin/ReservationController`, `Admin/PaymentController` (jointure `reservations ⟷ equipment ⟷ users ⟷ payments`).
- **Front** : `Front/ReservationController` (réserver, payer, annuler), `Front/EnergyAdvisorController`.
- **Formulaires avancés** : `ReservationRequest` (dates, durée max, chevauchement, propriétaire interdit via `after()`), `PaymentRequest` (montant ≤ total), `EnergyAdvisorRequest` (tableau dynamique `appliances.*`).
- **IA** : `Services/Ai/EnergyAdvisor` — calcule Wh/jour, dimensionne panneau + batterie, recommande le matériel **disponible**, rédige des conseils.

### Module 3 — Avis & signalements (Membre 3)

- **CRUD** : `Admin/ReviewController`, `Admin/ReviewReportController` (jointure `reviews ⟷ equipment ⟷ users`, `review_reports ⟷ reviews`).
- **Front** : `Front/ReviewController` (avis après location terminée, signalement).
- **Formulaires avancés** : `ReviewRequest`, `ReviewReportRequest` (`required_if:reason,other`).
- **IA** : `Services/Ai/ReviewAnalyzer` — sentiment (positif/neutre/négatif, score −1…1) et modération automatique : un avis toxique ou contenant un lien est masqué en attente de décision.

### Module 4 — Incidents & maintenance (Membre 4)

- **CRUD** : `Admin/IncidentController`, `Admin/MaintenanceTaskController` (jointure `maintenance_tasks ⟷ incidents ⟷ equipment ⟷ users`).
- **Front** : `Front/IncidentController` (déclaration + suivi).
- **Formulaires avancés** : `IncidentRequest` (catégorie/gravité facultatives côté front), `MaintenanceTaskRequest` (`completed_at` requis si terminée, ≥ date prévue).
- **IA** : `Services/Ai/IncidentTriage` — catégorie, gravité, résumé, action recommandée. Synchronisation automatique de l'état incident/équipement.

---

## 4. Conformité au cahier des charges

| Exigence | Réalisation |
|---|---|
| Laravel 12 | Squelette officiel `^12.0` |
| 2 templates Blade (front + back) | `layouts/front`, `layouts/admin` |
| ORM pour la persistance | **Eloquent** (ORM de Laravel) — 8 modèles + relations |
| Formulaires avancés + validation | 9 `FormRequest`, composants Blade, règles métier |
| 2 CRUD avec jointure par module | 4 × 2 entités, listes construites avec `join()` + `with()` |
| Fonctionnalité avancée IA | 1 par module, avec repli local |
| Authentification | Inscription, connexion, rôles `admin` / `user` |
| Plateforme collaborative | GitHub + `.github/workflows/ci.yml` |

## 5. Tests

```bash
php artisan test
```

`tests/Feature/SolarShareTest.php` : pages publiques, accès admin, chevauchement de réservations, assistant énergie, modération d'avis, triage d'incident.

## 6. Pour aller plus loin

- Docker + SonarQube (diapo « Technologies DevOps »).
- Passage à MySQL : modifier `DB_*` dans `.env`.
- Policies Laravel pour remplacer les contrôles `abort_unless`.
