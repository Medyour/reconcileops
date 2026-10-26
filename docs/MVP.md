# ReconcileOps — contrat du MVP

## Objectif

ReconcileOps rapproche un journal interne et un relevé prestataire fictifs. Le résultat doit être reproductible, explicable et inspectable sans exposer de donnée réelle.

## Pipeline

1. enregistrer un lot pour chacune des deux sources ;
2. valider et normaliser les lignes ;
3. détecter les doublons ;
4. appliquer les règles dans un ordre déterministe ;
5. publier atomiquement les correspondances et les exceptions ;
6. présenter les métriques et justifications dans une interface en lecture seule.

## Règles initiales

1. référence, montant et devise identiques ;
2. même référence avec une date comprise dans la fenêtre autorisée ;
3. combinaison déterministe de champs secondaires ;
4. doublon dans une source ;
5. correspondance ambiguë ;
6. ligne absente d’une source ;
7. écart de montant ;
8. devise incohérente.

Une règle plus faible ne doit jamais remplacer une correspondance déjà produite par une règle plus forte.

## Scénarios de démonstration

Le jeu initial couvre dix cas reproductibles : correspondance exacte, fenêtre de date, écart de montant, devise incohérente, doublon interne, doublon prestataire, absence interne, absence prestataire, ambiguïté et décision manuelle fictive historisée.

## Garanties

- un même lot ne peut être importé deux fois pour une même source ;
- une même paire de lots ne peut produire qu’une exécution ;
- une ligne ne peut être rapprochée qu’une fois dans une exécution ;
- un échec ne publie aucun résultat partiel ;
- les valeurs originales sont conservées pour l’audit mais ne sont pas sérialisées publiquement ;
- les montants utilisent des unités mineures entières ;
- toutes les données sont fictives.

## Version publique

La démonstration publique sera en lecture seule. Les imports, modifications de règles et décisions manuelles seront réservés aux environnements local et de test.

## Hors périmètre

- système bancaire, paiement ou ERP réel ;
- données personnelles ou financières réelles ;
- rapprochement probabiliste ou apprentissage automatique ;
- multi-tenant et facturation ;
- destinations ou imports réseau configurables.

