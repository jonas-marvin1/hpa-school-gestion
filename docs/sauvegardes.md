# Sauvegardes

Ce que Git couvre, ce qu'il ne couvre pas, et comment sauvegarder le reste.
Contexte d'hébergement complet dans
[docs/architecture.md](architecture.md#organisation-de-lhébergement) ;
procédure de déploiement où un export est requis dans
[docs/livraison.md](livraison.md#avant-tout-déploiement-en-production).

## Ce que Git couvre

Le code de l'application, dans ce dépôt — y compris `public/build`, versionné
par exception car le serveur ne peut pas compiler les assets (pas de `node`
disponible, voir [docs/architecture.md](architecture.md)).

## Ce que Git ne couvre jamais

- **Le `.env` de chaque environnement** (développement, recette, production)
  : jamais versionné, contient les secrets base de données, `APP_KEY` (clé
  qui signe les sessions) et la configuration mail. Aucune copie de secours
  n'est stockée dans ce dépôt.
- **Les trois bases de données**, isolées par utilisateur MySQL distinct :

  | Environnement | Base |
  |---|---|
  | Production | `u120571238_school_gestion` |
  | Recette | `u120571238_test_school` |
  | Site vitrine (projet séparé) | `u120571238_new_base_2026` |

  Les dumps SQL sont volontairement exclus du dépôt (`*.sql` dans
  `.gitignore`) : ils contiennent des données personnelles d'élèves.
- **Les fichiers déposés par les utilisateurs**
  (`storage/app/public` : photos de profil, rendus audio, pièces jointes).

## Ce qui existe aujourd'hui

Hostinger effectue des sauvegardes automatiques hebdomadaires de
l'hébergement. Au 24/08/2026, c'est le seul mécanisme de sauvegarde
identifié pour les bases de données et les fichiers utilisateurs — à
compléter systématiquement par un export manuel avant toute mise en
production (voir [docs/livraison.md](livraison.md)).

## Export manuel avant mise en production

Exporter la base de production depuis phpMyAdmin avant chaque déploiement.

**Règle absolue : ne jamais laisser un export `.sql` sur le serveur.** En
août 2026, un export de la base de production et une sauvegarde
`preinscription.php.bak` (site vitrine) sont restés téléchargeables
publiquement depuis la racine du site vitrine pendant près d'un mois. Un
export doit être téléchargé puis immédiatement supprimé du serveur, jamais
laissé « pour plus tard ».

## Vérifier un nouveau mot de passe de base avant de le reporter

Piège rencontré : hPanel accepte silencieusement un mot de passe trop court
sans l'enregistrer réellement. Toujours valider un nouveau mot de passe
avant de le reporter dans un `.env` :

```bash
mysql -h 127.0.0.1 -u <utilisateur> -p -e "SELECT 1;"
```

## Incident du `.env` exposé (contexte pour comprendre les rotations de secrets)

Le `.htaccess` protégeant la racine de l'application de production a été
absent du 2 au 21 août 2026 (dix-neuf jours) — voir
[docs/architecture.md](architecture.md) pour le mécanisme de protection.
Durant cette période, `.env` était potentiellement téléchargeable. Par
précaution, les mots de passe des trois bases et les `APP_KEY` de `my` et
`test` ont été changés le 22/08/2026. Si des identifiants antérieurs à cette
date sont retrouvés quelque part (documentation, notes, ancien `.env` en
sauvegarde locale), les considérer comme compromis et ne pas les réutiliser.

## Limite d'isolation à connaître

Base de données et fichiers utilisateurs mis à part, retenir que les trois
projets hébergés (cette application, le site vitrine, les deux WordPress)
partagent le même utilisateur système — une sauvegarde de l'un ne protège
pas d'une compromission via un autre. Détail dans
[docs/architecture.md](architecture.md#isolation-entre-projets--limite-à-connaître).
