# Architecture de ReconcileOps

## Principes

- règles de rapprochement déterministes et ordonnées ;
- séparation entre données originales, valeurs normalisées et décisions ;
- contraintes de base de données pour l’idempotence ;
- publication transactionnelle des résultats ;
- historique des décisions ajouté sans réécrire les faits précédents ;
- comportement identique sous SQLite et PostgreSQL.

## Agrégats

### Sources et imports

`data_sources` décrit les sources interne et prestataire. `import_batches` identifie un fichier logique par son empreinte. `source_records` conserve les valeurs utiles au rapprochement et la charge originale protégée.

L’action `ImportCsvBatch` lit et valide entièrement le fichier avant d’ouvrir une transaction. Elle contrôle la taille, l’UTF-8, l’en-tête exact, les types, les longueurs et les clés dupliquées. Une empreinte SHA-256 du fichier rend une répétition idempotente. Aucune ligne n’est enregistrée si le fichier contient une erreur.

### Exécutions

`reconciliation_runs` lie exactement un lot interne et un lot prestataire. Une contrainte unique empêche de recalculer silencieusement la même paire.

### Résultats

`reconciliation_matches` relie une ligne de chaque source avec la règle et la justification appliquées. `reconciliation_exceptions` représente les doublons, absences, ambiguïtés et écarts.

### Décisions

`reconciliation_decisions` ajoute l’historique d’une intervention simulée. Une décision ne supprime ni l’exception ni les données originales.

## Suite prévue

1. modèle de données et fixtures déterministes ;
2. import et normalisation ;
3. moteur de rapprochement transactionnel ;
4. dashboard et vues de détail ;
5. protection de la démonstration publique ;
6. CI multi-base et image de production ;
7. déploiement après validation complète.

