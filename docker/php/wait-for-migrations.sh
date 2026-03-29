#!/bin/sh
set -e
cd /var/www/php

echo "Waiting for backend migrations (marker file)..."
i=0
while [ ! -f storage/framework/.docker_migrations_ready ]; do
  i=$((i + 1))
  if [ "$i" -gt 120 ]; then
    echo "Timeout: storage/framework/.docker_migrations_ready missing after 4 minutes."
    exit 1
  fi
  sleep 2
done

echo "Migrations ready, starting queue worker."
exec php artisan queue:work database \
  --queue=mail \
  --tries=5 \
  --backoff=60 \
  --sleep=3 \
  --timeout=120 \
  --memory=256 \
  --max-jobs=1000 \
  --max-time=3600
