# ReconcileOps

ReconcileOps est une démonstration publique de rapprochement de données opérationnelles. Elle importe deux jeux fictifs, les normalise, applique des règles déterministes et rend chaque correspondance ou exception explicable.

> État : MVP fonctionnel déployé sur [reconcileops.mkirchel.com](https://reconcileops.mkirchel.com). Le dépôt ne crée ni ne met à jour automatiquement les ressources cloud.

## Capacités

- imports CSV contrôlés et traitements par lots ;
- normalisation et rapprochements SQL ;
- idempotence, transactions et résultats immuables ;
- détection des doublons, absences, ambiguïtés et écarts ;
- tableau de bord, historique et vues filtrables ;
- décisions fictives historisées uniquement en local ou pendant les tests ;
- démonstration publique strictement en lecture seule.

## Stack

- PHP 8.3 et Laravel 13 ;
- PostgreSQL 18 pour la cible de production ;
- SQLite pour le développement local rapide ;
- Blade, Tailwind CSS 4 et Vite 8 ;
- PHPUnit 12, Laravel Pint et GitHub Actions.

## Démarrage local

Pré-requis : PHP 8.3, Composer 2, Node.js 24 et npm.

```sh
composer run setup
php artisan db:seed
composer run dev
```

Pour activer l'interface locale de décision fictive, définir `RECONCILEOPS_ALLOW_DEMO_DECISIONS=true` dans le fichier `.env`, puis vider le cache de configuration. Cette valeur doit rester `false` en production.

## Validation

```sh
composer run check
npm audit --audit-level=low
npm run build
```

La CI exécute la suite sur SQLite et PostgreSQL 18, valide le formatage et les métadonnées Composer, audite les dépendances PHP et JavaScript, construit les ressources Vite et l'image de production.

## Déploiement DigitalOcean

Le fichier [`.do/app.yaml`](.do/app.yaml) décrit une application App Platform à Londres avec :

- un service web de taille `apps-s-1vcpu-0.5gb` ;
- une base PostgreSQL 18 de développement ;
- un job de préparation avant déploiement ;
- un reset quotidien à 04:00 Europe/Paris, facturé uniquement pendant son exécution.

L'auto-déploiement est désactivé. Avant toute création, vérifier l'estimation affichée par DigitalOcean et sélectionner manuellement le VPC `default-lon1`. Après l'import du spec, ajouter uniquement au composant `web`, avec portée `RUN_TIME` et type chiffré `SECRET`, une variable `APP_KEY` Laravel valide.

Le domaine principal est `reconcileops.mkirchel.com`. Le CNAME Cloudflare reste en mode **DNS only**. Le domaine de démarrage exact `reconcileops-7yflf.ondigitalocean.app` est limité par une redirection HTTPS 301 qui conserve le chemin et la chaîne de requête d'origine.

La procédure complète se trouve dans [docs/DIGITALOCEAN_RUNBOOK.md](docs/DIGITALOCEAN_RUNBOOK.md).

## Sécurité de la démonstration

- données entièrement fictives et réinitialisables ;
- aucun import, compte ou changement de décision accessible publiquement ;
- aucun secret, jeton ou identifiant d'infrastructure dans le dépôt ;
- montants stockés en unités mineures entières ;
- charges utiles originales exclues de la sérialisation publique ;
- configuration contrôlée avant démarrage ;
- en-têtes CSP, HSTS, anti-framing, anti-MIME-sniffing et politique de permissions ;
- reset transactionnel refusé si une donnée inconnue ou un compte existe.

## Documentation

- [Périmètre du MVP](docs/MVP.md)
- [Architecture](docs/ARCHITECTURE.md)
- [Runbook DigitalOcean](docs/DIGITALOCEAN_RUNBOOK.md)

## Licence

Le projet utilise une licence propriétaire de démonstration.
