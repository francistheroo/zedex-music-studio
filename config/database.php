<?php
declare(strict_types=1);

require_once __DIR__ . '/load_env.php';

/**
 * Read Neon credentials from the hosting environment. Never commit credentials.
 * Set DATABASE_URL to the Neon connection string (prefer the pooled connection).
 */
function database_connection(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $url = getenv('DATABASE_URL') ?: '';
    if ($url !== '') {
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host']) || empty($parts['path'])) {
            throw new RuntimeException('DATABASE_URL is not a valid PostgreSQL URL.');
        }
        $host = $parts['host'];
        $port = (int)($parts['port'] ?? 5432);
        $dbname = rawurldecode(ltrim($parts['path'], '/'));
        $user = rawurldecode((string)($parts['user'] ?? ''));
        $password = rawurldecode((string)($parts['pass'] ?? ''));
    } else {
        $host = getenv('DB_HOST') ?: '';
        $port = (int)(getenv('DB_PORT') ?: 5432);
        $dbname = getenv('DB_NAME') ?: '';
        $user = getenv('DB_USER') ?: '';
        $password = getenv('DB_PASSWORD') ?: '';
    }

    if ($host === '' || $dbname === '' || $user === '') {
        throw new RuntimeException('Database is not configured. Add your Neon DATABASE_URL in the hosting environment.');
    }

    $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s;sslmode=require', $host, $port, $dbname);
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
