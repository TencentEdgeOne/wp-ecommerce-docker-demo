#!/bin/bash
# entrypoint.sh

set -eo pipefail

sleep ${INIT_DELAY:-1}


echo "Initializing fresh SQL file..."
cp -v /app/db-init/data.sql /docker-entrypoint-initdb.d/

sed -i "s|\\\${WP_DOMAIN}|${WP_DOMAIN}|g" /docker-entrypoint-initdb.d/data.sql
sed -i "s|\\\${WP_HOST}|${WP_HOST}|g" /docker-entrypoint-initdb.d/data.sql

if ! grep -q "${WP_DOMAIN}" /docker-entrypoint-initdb.d/data.sql; then
    echo "WP_DOMAIN replacement failed" >&2
    exit 1
fi


exec /usr/local/bin/docker-entrypoint.sh "$@"