# Module Plans de paiement — guide de l'administrateur

Ce guide s'adresse à l'administrateur : la page **Plan de paiement** d'un
apprenant (accessible depuis sa fiche) n'est aujourd'hui ouverte qu'à ce
rôle. Il décrit la gestion d'un abandon de formation, la partie la moins
évidente de cet écran : trois gestes s'y côtoient, et se tromper entre eux
a des conséquences différentes sur le coût total et l'historique.

## Les trois gestes sur une échéance à venir

| Geste | Quand l'utiliser | Effet |
|---|---|---|
| **Annuler** | L'échéance était légitime mais n'est plus due dans l'immédiat (report, litige en cours...). | Reste visible dans le tableau, marquée « Annulée » avec son motif. Sort du solde dû. Le coût total ne change pas : la somme reste due sur le principe, à replanifier plus tard. |
| **Supprimer** | L'échéance n'aurait jamais dû exister — erreur de saisie (montant en double, mauvaise date...). | Disparaît du tableau. Le coût total diminue du même montant. Une trace de la suppression est ajoutée aux notes du plan. Impossible sur une échéance déjà réglée. |
| **Arrêter la formation** | L'apprenant abandonne définitivement, quel que soit le nombre d'échéances restantes. | Toutes les échéances non réglées passent en « Annulée » (rien n'est supprimé). Le coût total est ramené au montant déjà réglé. Le dossier sort des relances. |

**La règle pour choisir entre Annuler et Supprimer** : si la ligne devait
exister mais ne doit plus être payée maintenant, c'est **Annuler**. Si la
ligne n'aurait jamais dû apparaître dans le calendrier, c'est **Supprimer**.
En cas de doute, préférez **Annuler** : elle garde une trace, alors qu'une
suppression est définitive.

**Pourquoi l'arrêt annule au lieu de supprimer** : contrairement à une
échéance saisie par erreur, les échéances d'une formation abandonnée
étaient bien prévues — on veut garder la trace de ce qui devait être payé,
pas l'effacer comme si cela n'avait jamais existé.

## Arrêter une formation

1. Sur la page **Plan de paiement** de l'apprenant, cliquez sur **Arrêter
   la formation** (visible uniquement s'il reste au moins une échéance non
   réglée et non annulée).
2. La fenêtre de confirmation rappelle les montants réels du dossier : ce
   qui a été réglé, le nouveau coût total, et ce qui va être annulé.
   Un motif est possible mais facultatif.
3. Après confirmation, le dossier apparaît soldé (0 restant à payer) et ne
   figure plus dans les relances du tableau de bord ni dans les rappels
   automatiques envoyés à l'apprenant.

## Reprendre une formation arrêtée

Si l'apprenant revient plus tard, **n'ouvrez pas un second plan** : le
plan existant se prolonge.

1. Sur la même page, cliquez sur **+ Ajouter une échéance**.
2. Saisissez une date (dans le futur) et un montant.
3. Le coût total du plan augmente d'autant, l'échéance apparaît « à
   venir », et l'historique (paiements passés, échéances annulées) reste
   visible tel quel.

Ce même bouton sert aussi à ajuster un plan en cours (ajout d'un mois
supplémentaire, par exemple), pas seulement à reprendre un dossier arrêté.

## Modifier le coût total

Le coût total se saisit toujours à la main, dans **Modifier le plan**. Sa
modification recalcule les échéances à venir, sans jamais toucher aux
échéances déjà réglées ni aux échéances annulées. Un coût total ramené au
montant déjà réglé ne laisse aucune échéance à venir : le formulaire
accepte de l'enregistrer tel quel. Un coût total inférieur au montant déjà
réglé est refusé — cela reviendrait à afficher un solde négatif.

## L'avertissement de cohérence

Le coût total étant saisi à la main, il peut ne plus correspondre à la
somme du détail (montant réglé + échéances à venir) — par exemple après un
geste commercial. Ce n'est pas une erreur bloquante : un bandeau orange
apparaît simplement sur la page pour le signaler, à vérifier au cas par
cas.
