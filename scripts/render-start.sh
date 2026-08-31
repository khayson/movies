#!/usr/bin/env bash
set -euo pipefail

PORT="${PORT:-10000}"
sed -i "s/listen 80;/listen ${PORT};/" /var/www/html/conf/nginx/nginx-site.conf

exec /start.sh
