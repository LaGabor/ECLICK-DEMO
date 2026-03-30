#!/bin/sh
set -e

cd /var/www/php

git config --global --add safe.directory /var/www/php 2>/dev/null || true

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
php artisan db:seed --force

echo "Copying demo images into storage/public"
mkdir -p storage/app/public/demo
cp -f public/pics/product-pic.png storage/app/public/demo/product-pic.png
cp -f public/pics/receipt-pic.jpg storage/app/public/demo/receipt-pic.jpg

if [ ! -L public/storage ]; then
  php artisan storage:link
fi

touch storage/framework/.docker_migrations_ready

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

php-fpm
