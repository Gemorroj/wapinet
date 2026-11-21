#!/bin/sh
set -eu

if [ -n "${MYSQL_ROOT_PASSWORD:-}" ]; then
    set -- -uroot -p"$MYSQL_ROOT_PASSWORD"
else
    set -- -uroot
fi

mysql "$@" <<-EOSQL
    CREATE DATABASE IF NOT EXISTS \`${MYSQL_DATABASE}_test\`
        CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
EOSQL
