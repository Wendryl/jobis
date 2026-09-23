#!/bin/sh
set -e

export PORT=${PORT:-8080}

envsubst '${PORT}' < /var/www/html/docker/nginx.conf > /etc/nginx/http.d/default.conf

chown -R www-data:www-data /var/www/html/var /var/www/html/logs 2>/dev/null || true

php-fpm -D

nginx -g "daemon off;"
