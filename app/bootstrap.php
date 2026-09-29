<?php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_FILE', ROOT_PATH . '/config.php');
define('APP_INSTALLED', is_file(CONFIG_FILE));

// Production-safe PHP/session defaults. Errors are logged server-side and never shown to visitors.
@ini_set('display_errors','0');
@ini_set('log_errors','1');
if (is_dir(ROOT_PATH . '/storage')) {
    @ini_set('error_log', ROOT_PATH . '/storage/php-error.log');
}
@ini_set('session.use_strict_mode','1');
@ini_set('session.use_only_cookies','1');
@ini_set('session.cookie_httponly','1');
@ini_set('session.cookie_samesite','Lax');

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
if ($https) {
    @ini_set('session.cookie_secure','1');
}
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
    ensure_main_menu_schema();
    ensure_homepage_right_blocks_schema();
    ensure_right_blocks_area_schema();
    ensure_photo_gallery_schema();
    ensure_social_links_schema();
    ensure_video_gallery_schema();
    ensure_static_pages_schema();

    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $scriptBase = basename($scriptName);
    $isAdminRequest = str_contains($scriptName, '/admin/');

    if (
        maintenance_mode_enabled()
        && !$isAdminRequest
        && $scriptBase !== 'maintenance.php'
        && $scriptBase !== 'install.php'
        && !admin_user()
    ) {
        http_response_code(503);
        header('Retry-After: 3600');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        require ROOT_PATH . '/maintenance.php';
        exit;
    }
}
