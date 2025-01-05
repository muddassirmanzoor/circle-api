#!/bin/sh

# Clear Laravel cache

mkdir /var/www/html/bootstrap/cache

chown -R nobody:nobody /var/www/html/storage
chown -R nobody:nobody /var/www/html/bootstrap/cache

php artisan cache:clear

# Start supervisord
exec /usr/bin/supervisord -c /etc/supervisor.d/supervisord.ini

chown -vR nobody:nobody /var/lib/nginx/
chown -R nobody:nobody /var/www/html/storage/
chmod -vR u+w /var/lib/nginx/
nginx -s reload
