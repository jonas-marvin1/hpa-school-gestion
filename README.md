# HPA School Gestion

Application de gestion pour High Performance Academy, une école de langues :
planning des cours, suivi de la progression des apprenants, gestion des
paiements (fiches de paie des formateurs et échéancier des apprenants),
devoirs et messagerie interne.

## Pile technique

- Laravel 12, PHP 8.2
- Blade + Tailwind CSS 3 + Alpine.js (pas de framework front séparé)
- Spatie Laravel Permission pour les rôles
- MariaDB en développement, MySQL en recette/production (hébergement mutualisé)
- PHPUnit

Détails, pièges connus et conventions de code : voir [CLAUDE.md](CLAUDE.md).

## Les quatre rôles

`admin`, `manager`, `coach`, `student` — chacun avec son espace de contrôleurs
et son préfixe d'URL. Le détail des responsabilités (notamment financières)
et la segmentation des routes sont décrits dans [CLAUDE.md](CLAUDE.md#rôles-applicatifs)
et dans [docs/architecture.md](docs/architecture.md).

## Les trois environnements

| Environnement | Usage | Accès |
|---|---|---|
| Développement | Codespace GitHub, poste de travail | voir ci-dessous |
| Recette (`test`) | Validation d'une fiche avant mise en production | `test.hpacademya.com` |
| Production (`my`) | Application réelle | `my.hpacademya.com` |

Recette et production partagent le même hébergement mutualisé, dans la
racine web du site vitrine `hpacademya.com` (projet séparé). Le circuit de
livraison entre ces environnements est détaillé dans
[docs/livraison.md](docs/livraison.md).

## Démarrer en développement

Le projet est pensé pour tourner dans un Codespace GitHub. Au réveil du
conteneur :

```bash
bash .devcontainer/demarrage.sh
```

Ce script démarre MariaDB, attend qu'elle réponde, vide les caches Laravel et
relance `php artisan serve` sur le port 8000 — l'application s'ouvre alors
par l'onglet **Ports** du Codespace, jamais par `localhost:8000`. Détails et
pièges rencontrés : [CLAUDE.md](CLAUDE.md#environnement-de-développement).

Installation initiale (première ouverture du Codespace) :

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
```

## Commandes maison

| Commande | Rôle |
|---|---|
| `php artisan demo:prepare` | Prépare un jeu de données de recette pour les nouvelles fonctionnalités (dates recalculées à l'exécution, réexécutable). `--clean` le retire sans le recréer. |
| `php artisan reminders:send` | Envoie les rappels d'échéance de paiement et de devoirs aux apprenants concernés. Déclenchée en pratique par le middleware `DispatchDueReminders` à l'occasion des visites (au plus toutes les deux minutes), car aucun cron `schedule:run` n'est configuré sur l'hébergement — le scheduler Laravel de `routes/console.php` ne se déclenche donc jamais de lui-même. Détail : [docs/architecture.md](docs/architecture.md). |
| `php artisan mock:notifications` | Envoie des notifications factices pour tester le système de cloche, en développement. |

## Documentation

- [CLAUDE.md](CLAUDE.md) — contexte projet : pile technique, rôles,
  environnement de développement, conventions de code, organisation du
  travail hebdomadaire.
- [docs/architecture.md](docs/architecture.md) — modèle métier, décisions de
  conception, organisation de l'hébergement.
- [docs/livraison.md](docs/livraison.md) — procédure de déploiement, de la
  branche à la production, retour arrière compris.
- [docs/sauvegardes.md](docs/sauvegardes.md) — ce que Git couvre et ne
  couvre pas, comment sauvegarder le reste.
- [docs/fiches/](docs/fiches/) — une fiche par livraison hebdomadaire :
  avancement des points et décisions de périmètre prises en cours de
  développement.
