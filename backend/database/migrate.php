<?php
declare(strict_types=1);
require_once __DIR__.'/../config/config.php';
if (!is_pgsql()) { fwrite(STDERR, "DATABASE_URL absent: migration PostgreSQL ignorée.\n"); exit(0); }
$sql = file_get_contents(__DIR__.'/lessgo-postgres.sql');
if ($sql === false) throw new RuntimeException('Impossible de lire lessgo-postgres.sql');
db()->exec($sql);
echo "LessGo PostgreSQL: schema OK\n";
