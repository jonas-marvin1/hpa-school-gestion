# Architecture

Modèle métier et décisions de conception, tels que déduits du code. Public
visé : quelqu'un qui reprend le projet sans pouvoir poser de question.

## Vue d'ensemble en une phrase

`Program` › `Level` › `CourseClass` est l'arbre statique du curriculum ; les
utilisateurs sont rattachés à une `CourseClass` via un pivot marqué par un
rôle ; `ClassSession` est l'unité opérationnelle (une séance) qui se
prolonge en compte-rendu, présences et, une fois validée, une fiche de paie.
Les apprenants ont par ailleurs un échéancier de paiement indépendant des
séances, et une progression de niveau d'anglais tracée par un journal
générique plutôt que par une table dédiée.

## La chaîne Program → Level → CourseClass → ClassSession

- **`Program`** (table `programs`) : une offre de formation (ex. « Anglais
  général »). Pas de champ spécifique au-delà de `name`/`description`.
- **`Level`** (table `levels`) : une étape du curriculum au sein d'un
  programme (`belongsTo(Program)`), ordonnée par la colonne `order`. À ne
  pas confondre avec `EnglishLevel`, l'échelle CEFR utilisée pour la
  progression des apprenants (voir plus bas) — deux notions de « niveau »
  distinctes dans le code.
- **`CourseClass`** (table `course_classes`) : une classe concrète
  rattachée à un `Level`, avec dates de début/fin et lieu. Relations clés :
  - `classSessions()` → `hasMany(ClassSession)`
  - `users()` → `belongsToMany(User, 'course_class_user')->withPivot('role')`
    — le pivot porte un rôle (`coach` ou `student`) qui distingue
    l'appartenance d'un formateur de celle d'un apprenant à la même classe.
    Oublier `withPivot('role')` fait échouer silencieusement tout filtre par
    rôle sur cette relation.
- **`ClassSession`** (table `class_sessions`) : une séance planifiée ou
  tenue. Voir section suivante.

Les utilisateurs ne sont jamais rattachés directement à une séance : c'est
toujours via leur appartenance à la `CourseClass`, avec présence et
compte-rendu enregistrés séance par séance (`Attendance`, `SessionReport`).

## `ClassSession`, pivot du modèle

Statuts (liste canonique dans `Manager\ClassSessionController::STATUTS`) :
`scheduled` (prévue) → `completed` (effectuée) → `validated` (validée) →
ou `cancelled` (annulée) à tout moment.

Deux circuits de création :

- **Déclaration côté coach** (`Coach\SessionController::store()`) : le
  formateur déclare a posteriori une séance tenue. Contrôle de quota
  (voir plus bas), création directe avec `status = completed`, plus un
  `SessionReport` (progression, observations, recommandations) et une ligne
  `Attendance` par apprenant de la classe.
- **Création/édition côté manager** (`Manager\ClassSessionController`) :
  planification à l'avance, `status = scheduled` par défaut, tous les
  champs (type d'intervention, montant, lien visio). Une séance déjà
  facturée (`payment_id` renseigné) ne peut plus être modifiée.

Le passage à `status = validated` déclenche la génération de la fiche de
paie : `PaymentService::generatePendingPayments()` parcourt les séances
`validated` sans `payment_id`, crée un `Payment` par séance et rattache
`payment_id` à la séance (une séance = une fiche, dans l'état actuel du
code).

Une fenêtre de saisie encadre le compte-rendu de séance : ±15 minutes
autour du début/fin planifiés (`MARGE_RAPPORT_MINUTES`,
`fenetreRapportOuverte()`), pour éviter un compte-rendu rempli bien avant ou
longtemps après la séance.

## Progression de niveau d'anglais et traçabilité

La progression n'est **pas** stockée dans une table d'historique dédiée :
elle repose sur le trait générique `RecordsRevisions`
(`app/Models/Concerns/RecordsRevisions.php`), qui journalise automatiquement
toute création/modification/suppression d'un modèle qui l'utilise, quel que
soit le chemin de code (écran, commande Artisan, tinker). Chaque changement
produit une ligne dans la table `revisions` (polymorphe, append-only —
`public $timestamps = false`, pas de `updated_at`, rien n'y est jamais
supprimé ni réécrit).

Modèles utilisant `RecordsRevisions` : `User` (dont le niveau d'anglais),
`Submission`, `Grade`, `PaymentPlan`. Le même mécanisme sert donc à la fois
la progression des apprenants et l'historique des devoirs/notes.

`EnglishLevel` est l'échelle CEFR (`code`, `label`, `position`,
`suivant()`/`precedent()`). `User::changerNiveau()` assigne le niveau et
positionne un indicateur (`$revisionEstCorrection`, une propriété runtime,
jamais assignable en masse) juste avant la sauvegarde ; c'est ce
changement qui déclenche la ligne `revisions` avec `is_correction` à vrai
ou faux.

**`is_correction`** distingue une correction d'un niveau mal saisi (réservée
à l'admin) d'une évolution normale de niveau (déclenchable par un coach).
Contrôle serveur dans `StudentLevelController::update()` : le paramètre
`correction` n'est honoré que si l'utilisateur courant a le rôle `admin`,
même forgé dans la requête par un coach. Le journal reste intégralement
conservé ; seul l'**affichage** de l'historique (`historiqueNiveau()`)
tronque à la première correction rencontrée en remontant le temps, masquant
au passage la valeur erronée qui précédait cette correction. Le tableau de
bord admin et la page « Ma progression » de l'apprenant n'affichent que le
niveau courant, jamais l'historique : ils n'ont pas eu besoin d'évoluer pour
ce comportement.

## Les deux circuits financiers

### Fiches de paie formateur

Modèle `Payment` (table `payments`) : `coach_id`, `month`, `year`,
`total_sessions`, `total_amount`, `status` (`pending`/`paid`),
`validated_by`. Généré par `PaymentService::generatePendingPayments()` à
partir des séances validées (voir ci-dessus).

`Manager\PaymentController` centralise le filtrage (`filtrerPaiements()`,
réutilisé par `index()` et l'export CSV) : recherche par coach, statut,
mois, année, classe. Le règlement est réservé à l'admin, imposé côté route
(`routes/web.php`, groupe `role:admin` imbriqué) :

- `update()` — statut `paid`, renseigne `validated_by`.
- `markAsPaid()` / `markManyAsPaid()` — statut `paid` **sans** renseigner
  `validated_by`. Incohérence connue, documentée en dette technique dans
  [CLAUDE.md](../CLAUDE.md#dette-technique-connue) : la traçabilité du
  règlement dépend du bouton utilisé.
- `destroy()` — détache les séances (`payment_id = null`) avant de
  supprimer la fiche, ce qui les rend de nouveau facturables.

### Échéancier apprenant

Modèle `StudentPayment` (table `student_payments`) : `student_id`,
`program_id`, `payment_plan_id`, `amount`, `due_date`, `paid_date`,
`status` (`pending`/`paid`). Rattaché à un `PaymentPlan` (`total_amount`,
`advance_amount`, échéances via `echeances()`), qui expose des calculs
jamais stockés : `montantRegle()`, `soldeRestant()`, `progression()`,
`prochaineEcheance()`.

Le statut « en retard » **n'est jamais stocké** : la base ne connaît que
`pending`/`paid`. Il est recalculé à l'affichage, avec la même règle
partout (`statut != paid` et `due_date` dépassée) :
`Student\PaymentController` (vue apprenant), `Admin\StudentPaymentController`
(vision prévisionnelle admin, page ajoutée le 13/08/2026), et
`SendDueReminders`/`PaymentDueNotification` pour les rappels. Toute nouvelle
vue affichant ce statut doit reprendre cette même règle plutôt que
d'introduire une quatrième implémentation.

`Admin\StudentPaymentController::index()` calcule ses totaux
(attendu/réglé/à venir/en retard) sur l'ensemble des échéances du mois
filtré, indépendamment du filtre de statut appliqué au tableau — sinon
choisir « Payé » dans le tableau ferait aussi disparaître les autres totaux
de la vision prévisionnelle.

## Quotas de sessions

Table dédiée `session_quotas` (`course_class_id`, `year`, `month`, `quota`,
contrainte unique sur le triplet classe/année/mois) plutôt qu'une colonne
sur `course_classes` : le quota varie d'un mois à l'autre.

Trait `GuardsSessionQuota` (`app/Http/Controllers/Concerns/`), méthode
unique `verifierQuotaSession()` :

- sans quota saisi pour le couple classe/mois → aucun blocage (comportement
  par défaut, pas une erreur) ;
- « générées » = séances du mois non annulées (`status != cancelled`),
  toujours recalculées depuis `class_sessions`, jamais stockées ;
- « réalisées » = séances au statut `completed` ;
- la session en cours d'édition est exclue de son propre comptage, sinon un
  simple changement d'horaire se bloquerait lui-même.

Appliqué aux trois points d'entrée qui créent ou déplacent une séance :
déclaration coach (`Coach\SessionController::store()`), création manager et
modification manager (`Manager\ClassSessionController::store()`/`update()`).

Page de suivi (`Admin\SessionQuotaController`) réservée à `admin`, pas
exposée à `manager`.

## Décisions techniques transverses

### `AnneesDisponibles`

Service (`app/Services/AnneesDisponibles.php`) qui construit la liste
d'années proposée par tout filtre par année : années présentes en base, plus
l'année en cours, plus l'année suivante (`EN_AVANCE = 1`). Deux entrées
selon que la colonne source est un entier (`depuisColonneAnnee`, ex.
`payments.year`) ou une date (`depuisColonneDate`, ex.
`student_payments.due_date`, `class_sessions.start_time`) — dans les deux
cas, la fusion avec `[année courante, année courante + 1]` garantit que
l'année en cours reste toujours disponible sans tâche planifiée au
changement d'année. Tout nouveau filtre par année doit passer par ce
service plutôt que déduire ses propres bornes.

### Regroupement par date en PHP plutôt qu'en SQL

Les fonctions de date diffèrent entre MySQL (production/recette) et SQLite
(tests). `Admin\SessionQuotaController::index()` recalcule ainsi
« générées »/« réalisées » en PHP plutôt qu'avec une agrégation SQL, pour
rester portable entre moteurs sans requête spécifique à chacun. Le volume de
données d'une école ne justifie pas l'optimisation inverse.

### Liste blanche sur les filtres d'URL

Les valeurs de filtre venant de l'URL (statut, mois, classe...) sont
comparées à une liste blanche de valeurs attendues, jamais passées à
`validate()`. Une valeur fantaisiste dans l'URL doit être silencieusement
ignorée — un filtre n'est pas un formulaire de saisie, une erreur 422 y
serait déroutante pour l'utilisateur.

### `ExportsCsv`

Trait (`app/Http/Controllers/Concerns/ExportsCsv.php`), méthode unique
`streamCsv()` : BOM UTF-8 + séparateur point-virgule (pas la virgule
standard RFC 4180), pour qu'Excel FR ouvre le fichier nativement sans import
manuel. Chaque contrôleur qui l'utilise (`Admin\UserController`,
`Admin\ProgramController`, `Admin\LevelController`,
`Admin\CourseClassController`, `Admin\SubmissionArchiveController`,
`Manager\ClassSessionController`, `Manager\PaymentController`) réutilise la
même méthode de filtrage que son `index()`, pour que l'export corresponde
toujours exactement à ce qui est affiché à l'écran.

### `GuardsSessionQuota`

Voir « Quotas de sessions » ci-dessus.

## Modèles annexes

- **Devoirs** : `Assignment` (`belongsTo` `CourseClass` et `coach`) →
  `Submission` (`belongsTo` `Assignment` et `student`, `hasOne(Grade)`).
  `Submission` et `Grade` utilisent aussi `RecordsRevisions` : une
  resoumission ou une correction de note reste tracée, exploitée par
  `Admin\SubmissionArchiveController` (export « Devoirs archivés »).
- **Présence et retour** : `Attendance` (`belongsTo` `ClassSession` et
  `student`) porte le retour de l'*apprenant* sur la séance
  (`feedback`, `rating`) — à ne pas confondre avec `SessionReport`, le
  compte-rendu du *coach*, en relation un-à-un avec `ClassSession`.
- **`User`** est la table unique des quatre rôles (pas de table de profil
  séparée par rôle) : elle porte toutes les relations possibles
  (`courseClasses()`, `classSessions()` en tant que coach, `attendances()`
  et `submissions()` en tant qu'apprenant, `payments()` en tant que coach
  payé, `englishLevel()`). Elle utilise elle-même `RecordsRevisions` : tout
  changement sur un utilisateur est journalisé, pas seulement son niveau.
- **Notifications/messagerie** : `Message` et la table `notifications`
  (Laravel) alimentent une messagerie interne légère
  (`MessageController`/`NotificationController`), distincte des
  notifications de rappel d'échéance (`PaymentDueNotification`, envoyées par
  `reminders:send`).
- **Table abandonnée** : `payment_rules` a été créée puis supprimée
  (migration `2026_07_05_192258_drop_payment_rules_table.php`) — une
  première conception plus complexe de la paie a été abandonnée au profit
  du champ `amount` directement sur `ClassSession` agrégé par
  `PaymentService`. Ne pas chercher de modèle `PaymentRule`, il n'existe
  plus.

## Le planificateur Laravel — et son absence de cron

`reminders:send` est déclarée pour tourner chaque minute via le scheduler
Laravel (`routes/console.php`, `Schedule::command(...)->everyMinute()`), et
non une fois par jour, car un rappel de séance doit partir 20 minutes avant
celle-ci — une exécution quotidienne ne le permettrait pas. Mais le
scheduler Laravel ne s'exécute que si une tâche cron système appelle
`php artisan schedule:run` : **ce cron n'est pas configuré sur
l'hébergement**, donc cette planification ne se déclenche jamais d'elle-même.

En pratique, c'est le middleware `App\Http\Middleware\DispatchDueReminders`
qui déclenche l'envoi : à chaque visite, après l'envoi de la réponse HTTP
(`terminate()`), il relance `reminders:send` si plus de deux minutes se sont
écoulées depuis le dernier passage (verrou `Cache::lock` pour éviter un
double envoi entre deux requêtes simultanées). Les rappels ne partent donc
que s'il y a du trafic sur l'application, avec un délai de déclenchement
d'au plus deux minutes après l'échéance réelle plutôt qu'à la minute près.
Le jour où le cron `schedule:run` sera configuré, ce middleware devient
inoffensif : l'intervalle sera déjà respecté, il ne fera rien.

Configurer ce cron reste une dette technique côté hébergement, à traiter
lors d'une prochaine intervention (voir aussi
[docs/livraison.md](livraison.md)).

## Organisation de l'hébergement

Hostinger mutualisé (offre Premium, serveur LiteSpeed), PHP CLI 8.2.32 côté
serveur. Un seul utilisateur système, `u120571238`, partagé par trois
projets : ce dépôt, le site vitrine `hpacademya.com` (dépôt
`hpa-site-vitrine`, sans rapport avec ce projet au-delà de l'hébergement) et
deux sites WordPress sans rapport.

```
/home/u120571238/
├── public_html → domains/hpacademya.com/public_html   (lien symbolique)
├── hors-web/                    fichiers retirés de la zone web
└── domains/
    ├── hpacademya.com/
    │   ├── HPA/  in/            hors zone web, vestiges anciens
    │   └── public_html/         RACINE WEB du site vitrine
    │       ├── my/app/          application de PRODUCTION (suit `main`)
    │       └── test/app/        application de RECETTE
    ├── hpacademya.shop/         WordPress, sans lien avec ce projet
    └── myhpacademya.com/        WordPress, sans lien avec ce projet
```

Sous-domaines déclarés dans hPanel :

- `my.hpacademya.com` → `.../public_html/my/app/public`
- `test.hpacademya.com` → `.../public_html/test/app/public`

### Pourquoi les applications sont dans la racine web du site vitrine

Ce n'est pas un choix mais une contrainte, vérifiée le 24/08/2026 avant
d'être acceptée : sur Hostinger, la racine d'un sous-domaine se définit
**uniquement à sa création**, et le formulaire impose un préfixe
`/public_html/` non modifiable. Impossible donc de faire pointer un
sous-domaine hors de la racine web du domaine principal.

Trois pistes examinées et écartées :

- racine personnalisée hors `public_html` : refusée par le panneau ;
- lien symbolique vers un dossier extérieur : sans effet, le serveur suit le
  lien et l'arborescence reste atteignable ;
- déclarer les sous-domaines comme sites web autonomes : plausible mais non
  testé, jugé disproportionné.

Décision : garder l'organisation actuelle, protégée par le `.htaccess` à la
racine de l'application (voir le commentaire du fichier). Le critère qui
permettrait un jour de le retirer : que la racine du sous-domaine pointe
directement sur `app/public`, hors de `public_html`. Tant que ce n'est pas
le cas, il est indispensable — **ne jamais le supprimer**.

### Isolation entre projets — limite à connaître

Les trois projets tournent sous le même utilisateur Unix. Une compromission
du site vitrine (code PHP artisanal avec gestion d'uploads) donnerait accès
aux fichiers des applications de ce dépôt, quel que soit leur emplacement.
Seuls des comptes d'hébergement séparés, ou un VPS avec un utilisateur par
projet, apporteraient une vraie étanchéité. À garder en tête lors d'un futur
changement d'hébergement.

### Outillage disponible sur le serveur

SSH actif (`ssh -p 65002 u120571238@153.92.220.83`), avec `git`, `composer`
et `php` 8.2.32. **`node` et `npm` sont absents** : c'est pourquoi
`public/build` est versionné dans ce dépôt, contrairement à l'usage courant
— les assets ne peuvent pas être compilés sur le serveur. Toute
modification de CSS ou de classes Tailwind impose `npm run build` en local
puis un commit de `public/build`.

### `MaintenanceController` — dette technique à retirer

Ce contrôleur (`app/Http/Controllers/MaintenanceController.php`) permettait
de lancer des commandes Artisan depuis le navigateur (liste blanche de
commandes, jeton `MAINTENANCE_TOKEN` comparé à temps constant), à l'époque
où l'hébergement ne donnait pas accès à un terminal. Ce n'est plus le cas :
SSH est actif, et `MAINTENANCE_TOKEN` est absent des `.env` de recette et de
production — la route répond donc 404 par construction (voir
`verifierAcces()`). À supprimer lors d'un prochain passage.
