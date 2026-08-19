# Contexte Technique & Architecture - HPA School Gestion

## 1. Pile Technologique (Tech Stack)
- **Backend Framework** : Laravel 12.0
- **Langage** : PHP ^8.2
- **Base de données** : MySQL / MariaDB (géré via Eloquent ORM)
- **Frontend** : Blade Templates propulsés par Vite 7.0, Tailwind CSS (^3.1.0 / ^4.0.0), et Alpine.js (^3.4.2)
- **Sécurité & Accès** : Laravel Breeze (Authentification basique de session) et `spatie/laravel-permission` (^6.25) pour le RBAC.
- **Outils Qualité & Tests** : PHPUnit 11.5, Laravel Pint, Mockery.

## 2. Topologie Structurelle du Projet (Deep Analysis)
Le projet applique une séparation stricte des préoccupations (SoC) par le biais de namespaces et de sous-dossiers spécifiques pour chaque rôle utilisateur.

### `app/Http/Controllers/`
- **`/Admin`** : Contrôleurs pour la gestion des données de référence (Programmes, Niveaux, Utilisateurs, Règles de paiement).
- **`/Manager`** : Contrôleurs opérationnels (Groupes, Plannings/Sessions, Génération et paiement des factures formateurs).
- **`/Coach`** : Interfaces pédagogiques (Déclaration de sessions, Rapports de cours, Devoirs, Évaluations, Suivi de ses propres paiements).
- **`/Student`** : Interface apprenant (Rendus de devoirs, Feedbacks de sessions, Suivi paiements).
- **`DashboardController.php` (Racine)** : Un routeur d'aiguillage intelligent qui redirige l'utilisateur vers son sous-dashboard spécifique après connexion.

### `app/Models/` (Entités Clés & Relations)
- **`User`** : Étendu avec le trait `HasRoles`. Centralise les relations vers les classes (étudiants), les sessions (coachs), et les paiements.
- **Cycle Pédagogique** : `Program` -> `Level` -> `CourseClass` -> `ClassSession` -> `Attendance` / `SessionReport`.
- **Cycle Financier Coach** : `User (Coach)` -> `PaymentRule` (taux horaire/fixe) -> `ClassSession` (sessions réalisées) -> `Payment` (fiche de paie consolidée).
- **Cycle Évaluation** : `Assignment` -> `Submission` -> `Grade`.

### `routes/web.php`
L'architecture de routage est modulaire. Après les routes communes (profil, notifications, messages), les routes sont groupées par préfixes (`/admin`, `/manager`, `/coach`, `/student`) avec un middleware restrictif combinant `auth` et `role:*`.

### `resources/views/`
La couche présentation suit l'organisation des contrôleurs. Utilisation massive de composants Blade (ex: `x-app-layout`) pour factoriser le code HTML. La réactivité UI est gérée localement par Alpine.js (`x-data`, `x-show`) pour éviter la complexité de frameworks JS lourds.

## 3. Configuration et Environnement (`.env`)
- Localisation par défaut configurée en Français (`APP_LOCALE=fr`).
- Connexion base de données par défaut configurée sous MySQL (`DB_CONNECTION=mysql`).
- Fichiers de cache, queue, et session utilisant principalement la base de données (`SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`) pour éviter les dépendances Redis en phase initiale.
