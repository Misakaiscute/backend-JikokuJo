#!/bin/sh

set -e

php artisan key:generate --no-interaction

exec "$@"