# System Patterns & Conventions - HPA School Gestion

## 1. Architectural Paradigms
- **MVC (Model-View-Controller) Classique** : Respect strict du pattern MVC de Laravel. Les règles métier complexes doivent être encapsulées dans les modèles Eloquent ou des classes de service dédiées, afin de maintenir des contrôleurs fins ("Thin Controllers, Fat Models").
- **Ségrégation par Rôle (Namespace Segregation)** : Afin de prévenir toute fuite de privilèges, les ressources (Controllers, Views, Routes) sont physiquement isolées par rôle (`Admin`, `Manager`, `Coach`, `Student`). Un contrôleur racine (`DashboardController`) orchestre l'aiguillage initial post-login.

## 2. Modèles et Intégrité Relationnelle
- **Eloquent ORM** : Utilisation intensive des relations standards (`belongsTo`, `hasMany`, `belongsToMany`).
- **Cascade on Delete** : Au niveau de la base de données, l'intégrité référentielle est garantie par des clés étrangères configurées en cascade pour effacer proprement les enregistrements enfants (ex: supprimer une `ClassSession` supprime ses `Attendance`).

## 3. Sécurité et Autorisation (RBAC Matrix)
Géré par `spatie/laravel-permission`. L'application repose sur un système binaire Rôle/Middleware :
- Middleware `role:admin` : Accès exclusif à la configuration système.
- Middleware `role:manager|admin` : Accès aux outils opérationnels (l'admin peut toujours pallier un manager).
- Middleware `role:coach` : Portail formateur isolé.
- Middleware `role:student` : Portail apprenant isolé.
- *Règle de conception* : Aucune route métier ne doit être exposée sans protection par un middleware de rôle.

## 4. Design et Interface Utilisateur (UI/UX)
- **Framework CSS** : Tailwind CSS avec des conventions de classes utilitaires strictes.
- **Micro-interactions** : Gérées par Alpine.js pour les modales, les menus déroulants et les notifications flash, évitant l'usage de jQuery ou d'un lourd bundle JS.
- **Responsivité Tableaux** : Standard architectural imposant d'envelopper tous les tableaux de données dans `<div class="overflow-x-auto">` et d'appliquer `whitespace-nowrap` sur les en-têtes pour éviter la casse du design sur mobile.
- **Composants Blade** : Factorisation maximale (ex: boutons, formulaires, layouts) via les composants natifs de Laravel Blade (`<x-*>`).

## 5. Normes de Tests (TDD)
- **Tests Fonctionnels (Feature Tests)** : Séparés par domaine d'acteur (`tests/Feature/Admin`, `tests/Feature/Coach`, etc.).
- **Workflow** : Tout nouveau développement doit être validé par un test d'intégration garantissant que les autorisations (RBAC) fonctionnent correctement et que les données sont bien mutées dans la base de test.
