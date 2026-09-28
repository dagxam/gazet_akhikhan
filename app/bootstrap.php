<?php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_FILE', ROOT_PATH . '/config.php');
define('APP_INSTALLED', is_file(CONFIG_FILE));

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
    'httponly' => true,
    'secure' => $https,
    'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$config = APP_INSTALLED ? require CONFIG_FILE : require ROOT_PATH . '/config.example.php';
date_default_timezone_set($config['site']['timezone'] ?? 'Europe/Moscow');

require_once ROOT_PATH . '/app/db.php';
require_once ROOT_PATH . '/app/helpers.php';

if (APP_INSTALLED) {
    ensure_default_categories();
    ensure_article_categories_schema();
    ensure_newspapers_schema();
    ensure_documents_schema();
}
