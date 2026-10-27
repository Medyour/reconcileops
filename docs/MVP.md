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

Le moteur exclut d’abord toutes les lignes dont l’empreinte apparaît plusieurs fois dans un même lot. Il applique ensuite les règles exactes, la fenêtre de date puis la clé composite. Les références restantes deviennent des écarts de montant, de devise, de date, des ambiguïtés ou des absences. Une ligne prestataire ne peut participer qu’à un seul résultat.

L’interface publique expose la synthèse du dernier rapprochement, l’historique des exécutions et le détail explicable des correspondances, exceptions et décisions fictives. Ces routes sont strictement en lecture seule : aucun import ni changement de décision n’est disponible depuis la démonstration.

Des vues transversales permettent de filtrer les correspondances par règle ou référence, et les exceptions par type, état ou référence. La page Architecture expose le pipeline, la priorité des règles, les garanties et les limites assumées du démonstrateur.

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

## Contrat CSV initial

L’ordre des colonnes est strict : `record_key`, `external_reference`, `secondary_reference`, `occurred_on`, `amount_minor`, `currency`.

- `occurred_on` utilise le format `YYYY-MM-DD` ;
- `amount_minor` est un entier signé exprimé en unités mineures ;
- `currency` contient exactement trois lettres ;
- `record_key` est unique dans un fichier ;
- le fichier est encodé en UTF-8 et limité à 1 Mio par défaut ;
- une répétition du même fichier pour la même source retourne le lot existant.

## Version publique

La démonstration publique sera en lecture seule. Les imports, modifications de règles et décisions manuelles seront réservés aux environnements local et de test.

## Hors périmètre

- système bancaire, paiement ou ERP réel ;
- données personnelles ou financières réelles ;
- rapprochement probabiliste ou apprentissage automatique ;
- multi-tenant et facturation ;
- destinations ou imports réseau configurables.

