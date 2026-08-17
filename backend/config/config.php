<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $databaseUrl = trim((string) getenv('DATABASE_URL'));

    if ($databaseUrl === '') {
        throw new RuntimeException(
            'DATABASE_URL est manquante. Configure-la dans Render.'
        );
    }

    $url = parse_url($databaseUrl);

    if (
        $url === false ||
        empty($url['host']) ||
        empty($url['user']) ||
        !isset($url['path'])
    ) {
        throw new RuntimeException(
            'DATABASE_URL PostgreSQL invalide.'
        );
    }

    $host = $url['host'];
    $port = (int) ($url['port'] ?? 5432);
    $database = ltrim($url['path'], '/');
    $user = urldecode($url['user']);
    $password = urldecode($url['pass'] ?? '');

    $dsn = "pgsql:host={$host};port={$port};dbname={$database}";

    $pdo = new PDO(
        $dsn,
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    return $pdo;
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES |
        JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

