#!/bin/sh
set -e
cd /var/www/php

echo "Waiting for backend migrations + seed (marker file)..."
i=0
while [ ! -f storage/framework/.docker_migrations_ready ]; do
  i=$((i + 1))
  if [ "$i" -gt 120 ]; then
    echo "Timeout: storage/framework/.docker_migrations_ready missing after 4 minutes."
    exit 1
  fi
  sleep 2
done

QUEUE_NAME="${DOCKER_QUEUE_NAME:-mail}"
JOB_TIMEOUT="${DOCKER_QUEUE_TIMEOUT:-120}"
WORKER_MEMORY="${DOCKER_QUEUE_MEMORY:-256}"
QUEUE_TRIES="${DOCKER_QUEUE_TRIES:-5}"
QUEUE_BACKOFF="${DOCKER_QUEUE_BACKOFF:-60}"

echo "Backend ready (migrations + seed), starting queue worker (queue=${QUEUE_NAME}, timeout=${JOB_TIMEOUT}s, memory=${WORKER_MEMORY}M)."
exec php artisan queue:work database \
  --queue="${QUEUE_NAME}" \
  --tries="${QUEUE_TRIES}" \
  --backoff="${QUEUE_BACKOFF}" \
  --sleep=3 \
  --timeout="${JOB_TIMEOUT}" \
  --memory="${WORKER_MEMORY}" \
  --max-jobs=1000 \
  --max-time=3600
