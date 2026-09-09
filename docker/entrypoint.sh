#!/bin/sh
set -e

if [ -f artisan ]; then
    php artisan storage:link >/dev/null 2>&1 || true
fi

exec "$@"