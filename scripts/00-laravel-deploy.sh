#!/usr/bin/env bash
set -euo pipefail

echo "Running composer..."
composer install --no-dev --optimize-autoloader --no-interaction --working-dir=/var/www/html

echo "Linking storage..."
php /var/www/html/artisan storage:link --force || true

echo "Caching config..."
php /var/www/html/artisan config:cache

echo "Caching routes..."
php /var/www/html/artisan route:cache

echo "Caching views..."
php /var/www/html/artisan view:cache

echo "Running migrations..."
php /var/www/html/artisan migrate --force
