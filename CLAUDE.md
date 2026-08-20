# HPA School Gestion — contexte projet

Application de gestion d'une école de langues (High Performance Academy).
Ce fichier est lu automatiquement au démarrage : il évite de réexpliquer le
projet à chaque session.

## Pile technique

- Laravel 12, PHP 8.3
- Blade + Tailwind CSS 3 + Alpine.js (pas de framework front séparé)
- Spatie Laravel Permission pour les rôles
- MariaDB en développement, MySQL en production (hébergement mutualisé)
- PHPUnit — 63 tests, à garder au vert

## Rôles applicatifs

`admin`, `manager`, `coach`, `student`. Les routes sont segmentées par
middleware `role:` dans `routes/web.php`, chaque rôle ayant son préfixe
d'URL et son espace de noms de contrôleurs (`App\Http\Controllers\Admin\`,
`\Manager\`, `\Coach\`, `\Student\`).

Répartition des responsabilités financières :
- l'**admin** décaisse (règlement et suppression des fiches de paie) ;
- le **manager** prépare et consulte, il ne décaisse pas ;
- le **coach** consulte ses propres fiches ;
- l'**apprenant** consulte ses échéances.

## Environnement de développement

Codespace GitHub. Au réveil du conteneur :

```
bash .devcontainer/demarrage.sh
```

Ce script démarre MariaDB, attend qu'elle réponde, vide les caches Laravel
et relance `php artisan serve` sur le port 8000.

Pièges déjà rencontrés, à ne pas rechercher une seconde fois :
- le service de base s'appelle **`mariadb`**, pas `mysql` ;
- Debian ne fournit pas de paquet `mysql-server`, c'est `mariadb-server` ;
- l'application s'ouvre par l'onglet **Ports** du Codespace, jamais par
  `localhost:8000` ;
- tuer `artisan serve` ne suffit pas, le processus enfant `server.php`
  survit et garde le port 8000.

## Organisation du travail

Chaque jeudi soir, une fiche de tâches numérotées arrive (maintenance et
nouveaux développements mêlés).

- **Une branche par fiche**, nommée avec la date de réception :
  `maintenance/2026-08-13`. Elle part toujours de `main`, jamais de la
  branche de la semaine précédente.
- **Un commit par point**, jamais deux points dans un même commit — c'est ce
  qui permet de retirer un point isolé par `git revert` si la recette le
  refuse.
- **Message de commit** : verbe + description en français, numéro du point
  entre parenthèses à la fin.
  Exemple : `Ajout filtre par classe dans les fiches de paie (point 8)`
- Circuit de livraison : branche → environnement de test → production.

## Conventions de code

- **Commentaires en français, sans accents**, expliquant le *pourquoi* d'un
  choix et non ce que fait la ligne. Ne commenter que ce qui n'est pas
  évident à la lecture.
- **Libellés d'interface en français**, avec accents.
- Les listes de valeurs proposées dans les filtres sont **déduites des
  données** ou calculées, jamais écrites en dur — sinon il faut modifier le
  code chaque année.
- **Toute restriction d'accès se protège côté serveur** (middleware ou
  policy) *et* se masque côté vue. Masquer un bouton ne protège rien : la
  route doit refuser l'appel direct.
- Les regroupements par date se font **en PHP plutôt qu'en SQL** : les
  fonctions de date diffèrent entre MySQL et SQLite (utilisé par les tests),
  et le volume de données d'une école ne justifie pas une requête par moteur.
- Les valeurs venant de l'URL sont filtrées par **liste blanche**, jamais
  validées par `validate()` sur un formulaire de filtre — une valeur
  fantaisiste doit être ignorée, pas provoquer une erreur.
- Ne jamais committer de dump SQL : ils contiennent des données
  personnelles d'élèves. `*.sql` est dans `.gitignore`.

## Service partagé

`app/Services/AnneesDisponibles.php` construit les listes d'années des
filtres : années présentes en base, plus l'année en cours, plus l'année
suivante. Tout nouveau filtre par année doit l'utiliser.

## État de la fiche du 13/08/2026

Traités et commités :
- point 8 — filtre par classe dans les fiches de paie
- point 7 — règlement des fiches réservé à l'admin
- point 9 — date de création et filtres par période sur les utilisateurs
- point 1 — prise en compte des années futures dans tous les filtres

Restants :
- **point 2** — export CSV des données filtrées (planning, utilisateurs,
  paiements, et tout tableau filtrable du Back Office Admin)
- **point 3** — contrôle du nombre de sessions par classe et par mois, avec
  alerte en cas de dépassement du quota défini par l'admin
- **point 4** — correction du niveau initial et de l'historique de
  progression, la valeur erronée ne devant plus apparaître dans l'interface
- **point 5** — modification par l'apprenant de ses informations
  personnelles, mot de passe et photo, sauf son nom d'utilisateur
- **point 6** — vue globale des paiements attendus chaque mois, avec vision
  prévisionnelle et non plus seulement les échéances du jour et les retards

## Dette technique connue

- Scripts ad hoc à la racine (`fix_tables.php`, `update_migrations.php`,
  `update_models.php`) à migrer vers des commandes Artisan.
- `markAsPaid()` et `markManyAsPaid()` n'enregistrent pas `validated_by`,
  contrairement à `update()` : la traçabilité du règlement dépend du bouton
  utilisé.
- Aucun lien « Détails » ne mène à `manager.payments.show` depuis la liste
  des fiches ; la page n'est atteignable qu'en saisissant l'URL.
- Dépendances Tailwind possiblement enchevêtrées entre la v3 et le plugin
  `@tailwindcss/vite` v4 dans `package.json`.

## Attentes de travail

Avant de proposer une modification, lire le code existant concerné plutôt
que de supposer sa structure. Après chaque point : lancer `php artisan test`
et signaler tout test cassé avant de proposer le commit. Ne jamais créer de
migration sans l'annoncer explicitement au préalable.

## Tenue du journal

Apres chaque point livre, avant de proposer le commit, mettre a jour le
fichier de fiche correspondant dans `docs/fiches/` :

- passer la ligne du point a « Livre » dans le tableau d'avancement ;
- ajouter sous « Decisions de perimetre » toute decision non evidente prise
  pendant le developpement, avec sa raison en une phrase.

Ne consigner que ce qui ne se devine pas a la lecture du code.
