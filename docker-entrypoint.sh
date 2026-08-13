#!/bin/sh
set -e
if [ -n "${DATABASE_URL:-}" ]; then
  echo "[LessGo] Initialisation PostgreSQL..."
  php /var/www/html/backend/database/migrate.php
fi
exec apache2-foreground
