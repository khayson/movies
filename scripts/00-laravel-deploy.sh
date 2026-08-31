#!/usr/bin/env bash
set -euo pipefail

echo "Linking storage..."
php /var/www/html/artisan storage:link --force || true

echo "Caching config..."
php /var/www/html/artisan config:cache
