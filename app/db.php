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

    $d = $config['db'];
    $dsn = 'mysql:host=' . $d['host'] . ';port=' . ($d['port'] ?? '3306') . ';dbname=' . $d['name'] . ';charset=' . ($d['charset'] ?? 'utf8mb4');

    $pdo = new PDO($dsn, $d['user'], $d['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
