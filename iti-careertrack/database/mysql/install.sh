#!/usr/bin/env bash
# Installs (or RE-installs — this DROPS the database!) the CareerTrack MySQL schema.
# Usage: MYSQL_ROOT="mysql -uroot -p" ./install.sh [database_name]
set -euo pipefail
DB="${1:-careertrack}"
MYSQL="${MYSQL_ROOT:-mysql -uroot}"
DIR="$(cd "$(dirname "$0")" && pwd)"
read -r -p "This will DROP and recreate database '$DB'. Type YES to continue: " ok
[ "$ok" = "YES" ] || { echo "Aborted."; exit 1; }
$MYSQL -e "DROP DATABASE IF EXISTS \`$DB\`; CREATE DATABASE \`$DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
for f in 01_schema.sql 02_routines.sql 03_reference_data.sql 04_app_user.sql 05_regions_roles.sql 07_performance.sql 08_trade_master.sql 09_login_ids.sql 10_roles_v2.sql 11_blood_group.sql 12_auto_linking.sql; do
  echo "-> $f"; $MYSQL "$DB" < "$DIR/$f"
done
echo "Schema installed in '$DB'."
