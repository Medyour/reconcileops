# Runbook DigitalOcean — ReconcileOps

Ce runbook sépare volontairement la revue du code de la création des ressources payantes. La fusion de l'App Spec ne déploie rien : `deploy_on_push` reste désactivé.

## 1. Préconditions

- branche `main` propre et synchronisée ;
- CI SQLite, PostgreSQL 18 et image de production réussies ;
- App Spec validé par `doctl apps spec validate --schema-only` avec une configuration locale isolée ;
- aucun secret, UUID de compte, URL de connexion ou nom de VPC inscrit dans le dépôt ;
- estimation de coût DigitalOcean relue avant confirmation.

## 2. Import de l'App Spec

Importer [`.do/app.yaml`](../.do/app.yaml) depuis le dépôt `Medyour/reconcileops`, branche `main`.

Avant de créer l'application :

1. confirmer la région Londres (`lon`) ;
2. conserver un seul service web et PostgreSQL 18 ;
3. vérifier les jobs `prepare-demo` et `reset-demo` ;
4. confirmer que l'auto-déploiement est désactivé partout ;
5. sélectionner manuellement le VPC existant `default-lon1` ;
6. contrôler l'estimation financière affichée.

Ne pas ajouter l'identifiant du VPC au fichier versionné : il dépend du compte DigitalOcean.

## 3. Secret du service web

Générer une clé Laravel localement :

```powershell
php artisan key:generate --show
```

Ajouter la valeur dans DigitalOcean uniquement sur le composant `web` :

- clé : `APP_KEY` ;
- portée : `RUN_TIME` ;
- type : `SECRET` chiffré.

Ne pas placer `APP_KEY` sur les jobs, dans l'App Spec, dans GitHub ou dans une capture. Les jobs de migration et de reset n'en ont pas besoin.

## 4. Premier déploiement

Le job `prepare-demo` effectue, dans cet ordre :

1. contrôle de la configuration et de la connexion PostgreSQL ;
2. migrations non destructives avec `migrate --force` ;
3. reset exclusif des seules données fictives reconnues ;
4. contrôle des tables et compteurs attendus.

Le service web refuse ensuite de démarrer si la configuration, la clé, la base ou le jeu de démonstration est incomplet.

En cas d'erreur de permission sur le schéma `public` d'une base de développement vide, appliquer uniquement le correctif officiel DigitalOcean au rôle et à la base concernés, puis relancer le déploiement. Ne jamais utiliser `migrate:fresh`, ne jamais supprimer une base existante et vérifier les cibles avant toute commande SQL.

## 5. Domaine

Après attribution du domaine de démarrage DigitalOcean :

1. créer dans Cloudflare un CNAME `reconcileops` vers ce domaine exact ;
2. laisser le proxy en mode **DNS only** ;
3. attendre que `reconcileops.mkirchel.com` soit actif dans App Platform ;
4. vérifier HTTPS et `/health` ;
5. ajouter dans une PR séparée une redirection HTTPS 301 limitée au domaine de démarrage exact, sans `uri`, afin de préserver le chemin et la chaîne de requête.

Ne pas créer une redirection depuis tous les domaines : elle provoquerait une boucle sur le domaine principal.

## 6. Contrôles fonctionnels

Résultats attendus après propagation DNS :

- `https://reconcileops.mkirchel.com/` répond `200` ;
- `https://reconcileops.mkirchel.com/health` répond `204` ;
- les tableaux et détails montrent uniquement les données fictives ;
- aucun formulaire de décision locale n'est visible ;
- les en-têtes CSP, HSTS, anti-framing et anti-MIME-sniffing sont présents ;
- le job quotidien apparaît dans **Activity → Jobs** après sa première exécution.

## 7. Exploitation et repli

- le reset quotidien s'exécute à 04:00 Europe/Paris ;
- un reset qui détecte un compte ou une donnée inconnue s'arrête sans suppression ;
- l'auto-déploiement reste désactivé ;
- pour revenir en arrière, redéployer explicitement le dernier déploiement sain ;
- avant toute destruction, exporter ce qui doit être conservé, vérifier les dépendances DNS puis supprimer l'application et sa base depuis DigitalOcean.
