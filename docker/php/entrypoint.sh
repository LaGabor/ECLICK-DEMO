#!/bin/sh
set -e

cd /var/www/php

rm -f storage/framework/.docker_migrations_ready

echo "Installing composer dependencies"

if [ ! -d vendor ]; then
  composer install
fi

echo "Waiting for DB"

until mysql --skip-ssl -h mysql -P 3306 -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "select 1"; do
  sleep 2
done

echo "Running migrations"

php artisan migrate --force
touch storage/framework/.docker_migrations_ready
php artisan db:seed --force

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

php-fpm
