#!/bin/sh
set -eu
php artisan reconcileops:production-check --database --no-interaction
php artisan reconcileops:demo-reset --force --exclusive --no-interaction
php artisan reconcileops:production-check --ready --no-interaction
