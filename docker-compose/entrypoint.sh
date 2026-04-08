#!/bin/bash

set -e

# Ensure the necessary directories are owned by www-data
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Helper function to run MySQL commands
mysql_cmd() {
    if [ -z "$DB_PASSWORD" ]; then
        mysql -h "${DB_HOST}" -u "${DB_USERNAME}" --skip-ssl "$@"
    else
        mysql -h "${DB_HOST}" -u "${DB_USERNAME}" -p"${DB_PASSWORD}" --skip-ssl "$@"
    fi
}
# Check if migrations table exists
MIGRATIONS_TABLE_EXISTS=$(mysql_cmd -se "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '${DB_DATABASE}' AND table_name = 'migrations';")
if [ "$MIGRATIONS_TABLE_EXISTS" -lt 1 ]; then
    echo "Migrations table does not exist."
    php artisan db:init
else
    MIGRATIONS_RAN=$(mysql_cmd -se "USE ${DB_DATABASE}; SELECT COUNT(*) FROM migrations;")
    if [ "$MIGRATIONS_RAN" -lt 1 ]; then
        echo "Migrations have not been run yet."
        php artisan db:init
    else
        echo "Migrations have already been run."
        
        # Check if 'feed_info' table exists
        FEED_INFO_TABLE_EXISTS=$(mysql_cmd -se "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '${DB_DATABASE}' AND table_name = 'feed_info';")
        
        if [ "$FEED_INFO_TABLE_EXISTS" -lt 1 ]; then
            echo "Feed info table does not exist. Fetching feed info."
            php artisan db:init
        else
            CURRENT_DATE=$(date +%s)
            FEED_VALID_UNTIL_RAW=$(mysql_cmd -se "USE ${DB_DATABASE}; SELECT end_date FROM feed_info ORDER BY end_date ASC LIMIT 1;")
            FEED_VALID_UNTIL=$(date -d "$FEED_VALID_UNTIL_RAW" +%s)
            
            if [ "$CURRENT_DATE" -ge "$FEED_VALID_UNTIL" ]; then
                echo "Feed info is out of date. Refetching."
                php artisan db:init
            else
                echo "Feed info is valid. No need to fetch."
            fi
        fi
    fi
fi

# Generate Laravel key
php artisan key:generate --no-interaction

# Execute the remaining command
exec "$@"