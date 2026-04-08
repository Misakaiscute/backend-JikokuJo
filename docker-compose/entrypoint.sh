#!/bin/sh

set -e

php artisan db:init

php artisan key:generate --no-interaction

exec "$@"