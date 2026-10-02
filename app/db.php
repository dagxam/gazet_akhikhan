<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    global $config;

    if ($pdo instanceof PDO) {
        return $pdo;
    }
    if (!APP_INSTALLED) {
        throw new RuntimeException('Сайт еще не установлен.');
    }

    $d = $config['db'] ?? [];
    $driver = strtolower((string)($d['driver'] ?? 'mysql'));

    if ($driver === 'sqlite') {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            throw new RuntimeException('На сервере не включено расширение PDO_SQLITE.');
        }

        $path = (string)($d['path'] ?? 'storage/akhikhan.sqlite');
        if ($path === '') {
            throw new RuntimeException('Не указан путь к SQLite базе.');
        }
        if ($path[0] !== '/' && !preg_match('~^[A-Za-z]:[\\\\/]~', $path)) {
            $path = ROOT_PATH . '/' . ltrim($path, '/\\');
        }

        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA busy_timeout = 5000');
        return $pdo;
    }

    $host = (string)($d['host'] ?? 'localhost');
    $port = (string)($d['port'] ?? '3306');
    $name = (string)($d['name'] ?? '');
    $charset = (string)($d['charset'] ?? 'utf8mb4');

    $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $name . ';charset=' . $charset;
    $pdo = new PDO($dsn, (string)($d['user'] ?? ''), (string)($d['pass'] ?? ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Keep MySQL CURRENT_TIMESTAMP in the same timezone as PHP/publication inputs.
    // published_at is stored as a local DATETIME, so comparing it against a
    // server running in another timezone can hide freshly published articles.
    try {
        $timezoneName = (string)($config['site']['timezone'] ?? 'Europe/Moscow');
        $timezone = new DateTimeZone($timezoneName);
        $now = new DateTimeImmutable('now', $timezone);
        $offsetSeconds = $timezone->getOffset($now);
        $sign = $offsetSeconds < 0 ? '-' : '+';
        $offsetSeconds = abs($offsetSeconds);
        $hours = intdiv($offsetSeconds, 3600);
        $minutes = intdiv($offsetSeconds % 3600, 60);
        $mysqlOffset = sprintf('%s%02d:%02d', $sign, $hours, $minutes);
        $pdo->exec('SET SESSION time_zone=' . $pdo->quote($mysqlOffset));
    } catch (Throwable $e) {
        error_log('[db timezone] ' . $e->getMessage());
    }

    return $pdo;
}
