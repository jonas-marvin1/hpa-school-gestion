# Livraison

Procédure de déploiement, de la branche de développement à la production.
Contexte d'hébergement détaillé dans
[docs/architecture.md](architecture.md#organisation-de-lhébergement) ; ce
qui n'est pas sauvegardé par cette procédure est couvert par
[docs/sauvegardes.md](sauvegardes.md).

## Circuit

```
branche de la fiche (depuis main)
        │
        ▼
   recette (test.hpacademya.com)
        │  validation
        ▼
   merge --no-ff dans main
        │
        ▼
   tag livraison-AAAA-MM-JJ
        │
        ▼
   production (my.hpacademya.com)
```

- Une branche par fiche, nommée par la date de réception
  (`maintenance/AAAA-MM-JJ`), toujours créée depuis `main` — voir
  [CLAUDE.md](../CLAUDE.md#organisation-du-travail) pour la convention de
  commit (un commit par point).
- Une fois la branche validée sur la recette, elle est fusionnée dans `main`
  avec `--no-ff` (préserve la branche comme unité dans l'historique), puis
  un tag `livraison-AAAA-MM-JJ` est posé sur ce commit de fusion.
- `origin/main` à jour ne garantit pas que le serveur a déjà récupéré ce
  commit : chaque déploiement est un geste manuel, distinct du merge GitHub.

Dernier tag posé : `livraison-2026-08-13`.

## Accès serveur

```
ssh -p 65002 u120571238@153.92.220.83
```

`test/app` et `my/app` sont des clones Git indépendants du dépôt (voir
schéma des chemins dans
[docs/architecture.md](architecture.md#organisation-de-lhébergement)).
`my` suit `main`.

## Avant tout déploiement en production

1. Export de la base de production depuis phpMyAdmin (voir
   [docs/sauvegardes.md](sauvegardes.md) — ne jamais laisser ce fichier sur
   le serveur ensuite).
2. Vérifier que `APP_URL` du `.env` de production vaut exactement
   `https://my.hpacademya.com`. `AppServiceProvider` force la racine de
   toutes les URL de l'application sur cette valeur, dans tous les
   environnements : une `APP_URL` erronée casse tous les liens du site, pas
   seulement quelques-uns.

## Séquence de déploiement

À exécuter dans `.../public_html/my/app/` (adapter en `test/app/` pour un
déploiement de recette — l'étape 1 ne s'applique alors pas).

```bash
# 1. Mode maintenance (production uniquement)
php artisan down

# 2. Récupérer la branche ou le tag à déployer
git fetch origin
git checkout -f <branche ou tag>

# 3. Relire ce qui va être supprimé avant de nettoyer l'arborescence,
#    puis nettoyer en préservant les sauvegardes de .env
#    Ne jamais lancer le "git clean -fd" si la sortie de "git clean -nd"
#    liste un fichier ".env.sauvegarde-*" : l'option -e ci-dessous l'exclut
#    en théorie, mais un nom de fichier inattendu (mauvaise date, mauvaise
#    extension) passerait au travers du filtre et serait supprimé.
git clean -nd
git clean -fd -e ".env.sauvegarde-*"

# 4. Dépendances PHP
#    --no-dev en production ; sur la recette, les dépendances de dev sont
#    nécessaires à demo:prepare, qui dépend de Faker
composer install --no-dev --optimize-autoloader

# 5. Vider les caches de configuration, routes, vues
php artisan optimize:clear

# 6. Migrations — relire l'état avant d'appliquer
php artisan migrate:status
php artisan migrate --force

# 7. Lien vers les fichiers déposés par les utilisateurs
#    Ne sert qu'au premier déploiement d'un environnement : sur un
#    environnement déjà déployé, la commande répond « The [public/storage]
#    link already exists ». Ce n'est pas une erreur, poursuivre la séquence.
php artisan storage:link

# 8. Sortir du mode maintenance (production uniquement)
php artisan up
```

Node et npm sont absents du serveur : les assets compilés
(`public/build`) doivent déjà être commités dans la branche déployée. Toute
modification de CSS ou de classes Tailwind impose `npm run build` en local
avant de committer.

## Retour arrière

- **Retirer un point isolé, avant merge dans `main`** : `git revert <hash du
  commit du point>` sur la branche de la fiche, puis redéployer sur la
  recette.
- **Revenir à la livraison précédente, après déploiement en production** :

  ```bash
  git checkout <tag de la livraison précédente>
  ```

  puis rejouer la séquence de déploiement ci-dessus à partir de l'étape 4.
  Si la livraison qui a échoué a introduit une migration, vérifier si elle
  doit être annulée manuellement (`php artisan migrate:rollback` n'est pas
  automatique dans cette séquence) avant de redéployer.

## Faux positif connu du scanner Hostinger

Le scanner de l'hébergeur a déjà signalé comme malveillante l'archive
`phpunit/php-code-coverage` présente dans le cache Composer (`~/cache/`),
déposée par `composer install` sur la recette — un faux positif connu,
hérité d'une faille ancienne visant `eval-stdin.php` (CVE-2017-9841). Le
fichier se trouvait hors de toute racine web, sans risque réel. Ne pas
perdre de temps à réinvestiguer si l'alerte réapparaît.
