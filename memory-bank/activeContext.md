# Active Context - HPA School Gestion

## 1. État Actuel du Système
- **Fondations Stables** : Le framework Laravel 12 est configuré. Le système d'authentification (Breeze) couplé aux permissions (Spatie) est opérationnel.
- **Intégration UI Terminée** : Les tableaux de bord spécifiques aux rôles sont implémentés et le routage principal est testé et fonctionnel (via un routage intelligent sur la racine `/dashboard`).
- **Fonctionnalités Clés Actives** : 
  - La déclaration des sessions par les coachs.
  - L'alignement des colonnes entre le planning et les fiches de paie.
  - Le calcul dynamique des "Totaux à payer à ce jour" pour la supervision manager et la consultation coach.
- **Tests** : La suite de tests automatisés (PHPUnit) comporte une couverture adéquate des fonctionnalités critiques (plus de 50 tests) et est à 100% au vert.
- **Synchronisation DB** : La base de données locale a été re-synchronisée avec succès via un dump de sauvegarde récent et les migrations Laravel.

## 2. Décisions Techniques Récentes
- **Création du DashboardController global** : Pour résoudre des exceptions de type `BindingResolutionException`, un contrôleur racine a été conçu pour intercepter la connexion initiale et rediriger l'utilisateur vers la bonne URL de sous-dashboard en fonction de son rôle (`/admin/dashboard`, `/manager/dashboard`, etc.).
- **Refactoring Navigation** : L'interface `navigation.blade.php` a été remaniée avec des menus déroulants (`Administration`, `Finances`) pour prévenir la casse du layout de la barre de navigation sur les écrans moyens/petits.
- **Alignement des Vues Financières** : Remplacement des horaires de début/fin séparés par une colonne unifiée `Horaires` sur les fiches de paie, garantissant l'homogénéité avec la vue planning.

## 3. Dette Technique et Points de Vigilance (Observations de l'Audit)
- **Scripts Ad-hoc à la racine** : Plusieurs fichiers de scripts d'intervention manuelle (`fix_tables.php`, `update_migrations.php`, `update_models.php`) polluent la racine du projet. 
  - *Action requise future* : Migrer ces logiques vers des commandes Artisan (`php artisan make:command`) ou des Seeders pour respecter les standards de propreté du framework.
- **Conflits de Dépendance Tailwind** : Le fichier `package.json` liste potentiellement des dépendances enchevêtrées entre Tailwind v3.1 et le nouveau plugin `@tailwindcss/vite` (v4.0.0).
  - *Action requise future* : Auditer l'environnement Vite et nettoyer les dépendances obsolètes pour éviter des comportements CSS instables lors du build de production.
- **Localisation partielle** : Bien que configurée en français (`APP_LOCALE=fr`), certaines erreurs de validation ou textes statiques en dur pourraient nécessiter une révision pour utiliser le système de traduction standard de Laravel (`lang/fr/`).
