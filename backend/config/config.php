<?php
// backend/config/config.php
declare(strict_types=1);

/**
 * Database configuration.
 * Render: set DATABASE_URL (PostgreSQL connection string).
 * XAMPP/local: leave DATABASE_URL empty and MySQL defaults are used.
 */
function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $databaseUrl = getenv('DATABASE_URL') ?: '';
    if ($databaseUrl !== '') {
        $url = parse_url($databaseUrl);
        if (!$url || empty($url['host']) || empty($url['user']) || !isset($url['path'])) {
            throw new RuntimeException('DATABASE_URL PostgreSQL invalide.');
        }
        $host = $url['host'];
        $port = (int)($url['port'] ?? 5432);
        $name = ltrim($url['path'], '/');
        $user = urldecode($url['user']);
        $pass = urldecode($url['pass'] ?? '');
        $dsn = "pgsql:host={$host};port={$port};dbname={$name}";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $name = getenv('DB_NAME') ?: 'lessgo_community';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function is_pgsql(): bool { return (bool)(getenv('DATABASE_URL') ?: ''); }

function json_response(array $data, int $status=200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
