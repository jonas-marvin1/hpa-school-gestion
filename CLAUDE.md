# HPA School Gestion — contexte projet

Application de gestion d'une école de langues (High Performance Academy).
Ce fichier est lu automatiquement au démarrage : il évite de réexpliquer le
projet à chaque session.

## Pile technique

- Laravel 12, PHP 8.2
- Blade + Tailwind CSS 3 + Alpine.js (pas de framework front séparé)
- Spatie Laravel Permission pour les rôles
- MariaDB en développement, MySQL en production (hébergement mutualisé)
- PHPUnit — 86 tests, à garder au vert

Modèle métier et décisions de conception : voir
[docs/architecture.md](docs/architecture.md).

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
  survit et garde le port 8000 ;
- Alpine.js compile `@submit`/`@click` comme une **expression**, pas comme
  un corps de fonction : `@submit="return confirm(...)"` est donc invalide
  (le `return` en tête), Alpine l'ignore silencieusement et l'action part
  sans confirmation. Écrire `@submit.prevent="if (confirm(...)) $el.submit()"`.
- dans Alpine, `$el` est l'élément sur lequel l'expression courante est
  évaluée (pas la racine du composant), `$root` est la racine du composant
  `x-data` : tout parcours du DOM depuis un composant (`querySelectorAll`,
  etc.) doit partir de `$root`, sinon un `@change`/`@click` posé sur un
  élément différent de la racine cherche au mauvais endroit sans erreur.

## Déploiement et sauvegardes

Hébergement mutualisé, sans CI/CD : aucune GitHub Action ni script de
déploiement n'existe dans ce dépôt. Le passage en production est une étape
manuelle, détaillée pas à pas dans
[docs/livraison.md](docs/livraison.md) — organisation de l'hébergement,
séquence de déploiement, retour arrière.

Ce que Git sauvegarde et ne sauvegarde jamais (secrets, bases de données,
fichiers utilisateurs) : voir [docs/sauvegardes.md](docs/sauvegardes.md).

**Ne jamais supprimer le `.htaccess` à la racine de l'application** : sans
lui, `.env` devient téléchargeable depuis `hpacademya.com`. Détail dans
[docs/architecture.md](docs/architecture.md).

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
- `public/build` est versionné, contrairement à l'usage courant : l'hébergement
  n'a ni `node` ni `npm`, les assets ne peuvent pas y être compilés. Toute
  modification de CSS ou de classes Tailwind impose donc `npm run build` en
  local, puis un commit du résultat — sinon la production perd son apparence
  au déploiement.

## Service partagé

`app/Services/AnneesDisponibles.php` construit les listes d'années des
filtres : années présentes en base, plus l'année en cours, plus l'année
suivante. Tout nouveau filtre par année doit l'utiliser.

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
- `MaintenanceController` (exécution de commandes Artisan depuis le
  navigateur) n'a plus lieu d'être depuis que l'accès SSH est actif ; à
  supprimer lors d'un prochain passage. Détail dans
  [docs/architecture.md](docs/architecture.md).
- `assignments.coach_id` et `Grade.coach_id` désignent respectivement le
  créateur et le correcteur d'une évaluation, quel que soit son rôle réel
  (coach ou gestionnaire) depuis que la gestionnaire peut aussi créer et
  corriger des évaluations — noms conservés tels quels, une migration sur
  une clé étrangère en production ne se justifie pas pour un gain de
  nommage.

## Attentes de travail

Avant de proposer une modification, lire le code existant concerné plutôt
que de supposer sa structure. Après chaque point : lancer `php artisan test`
et signaler tout test cassé avant de proposer le commit. Ne jamais créer de
migration sans l'annoncer explicitement au préalable.

## Tenue du journal

Après chaque point livré, avant de proposer le commit, mettre à jour le
fichier de fiche correspondant dans `docs/fiches/` :

- passer la ligne du point à « Livré » dans le tableau d'avancement ;
- ajouter sous « Décisions de périmètre » toute décision non évidente prise
  pendant le développement, avec sa raison en une phrase.

Ne consigner que ce qui ne se devine pas à la lecture du code.
