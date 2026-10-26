# ReconcileOps

ReconcileOps est un démonstrateur public de rapprochement de données opérationnelles. Il importe deux jeux de données fictifs, les normalise, applique des règles déterministes et rend chaque correspondance ou exception explicable.

Le projet illustre les sujets suivants :

- imports CSV contrôlés et traitements par lots ;
- normalisation et rapprochements SQL ;
- idempotence, transactions et résultats immuables ;
- détection des doublons, absences, ambiguïtés et écarts ;
- tableaux de bord et décisions auditables.

La version publique prévue sera strictement en lecture seule et utilisera exclusivement des données fictives. Aucun système bancaire, ERP ou prestataire réel ne sera connecté.

## État

Le modèle de données initial et les scénarios de démonstration sont en cours de construction. Le périmètre fonctionnel se trouve dans [docs/MVP.md](docs/MVP.md) et les décisions techniques dans [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

## Stack

- PHP 8.3 et Laravel 13 ;
- PostgreSQL 18 pour la cible de production ;
- SQLite pour le développement local rapide ;
- Blade, Tailwind CSS 4 et Vite ;
- PHPUnit, Laravel Pint et GitHub Actions.

## Installation locale

```sh
composer run setup
```

## Tests

```sh
composer test
```

## Sécurité des données

- aucune donnée personnelle ou financière réelle ;
- aucun import public dans la future démonstration en production ;
- aucune destination réseau arbitraire ;
- montants fictifs stockés en unités mineures entières ;
- charges utiles originales exclues de la sérialisation publique.

