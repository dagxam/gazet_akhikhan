<?php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_FILE', ROOT_PATH . '/config.php');
define('APP_INSTALLED', is_file(CONFIG_FILE));

// Reject abnormally large request targets before PHP opens sessions or queries the database.
// Normal public URLs are far smaller; this reduces parser/database abuse and accidental resource exhaustion.
$requestUriRaw=(string)($_SERVER['REQUEST_URI']??'');
$queryStringRaw=(string)($_SERVER['QUERY_STRING']??'');
if(strlen($requestUriRaw)>4096 || strlen($queryStringRaw)>2048){
    http_response_code(414);
    header('Cache-Control: no-store');
    header('Connection: close');
    exit('Request URI Too Long');
}
if(preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',$requestUriRaw)){
    http_response_code(400);
    header('Cache-Control: no-store');
    header('Connection: close');
    exit('Bad Request');
}

// Production-safe PHP/session defaults. Errors are logged server-side and never shown to visitors.
@ini_set('display_errors','0');
@ini_set('log_errors','1');
if (is_dir(ROOT_PATH . '/storage')) {
    @ini_set('error_log', ROOT_PATH . '/storage/php-error.log');
}
@ini_set('session.use_strict_mode','1');
@ini_set('session.use_only_cookies','1');
@ini_set('session.use_trans_sid','0');
@ini_set('session.cookie_httponly','1');
@ini_set('session.cookie_samesite','Lax');
@ini_set('session.lazy_write','1');
@ini_set('session.gc_maxlifetime',(string)(4*3600));

if(session_status()===PHP_SESSION_NONE && session_name()==='PHPSESSID'){
    @session_name('AKHSESSID');
}

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
if ($https) {
    @ini_set('session.cookie_secure','1');
}
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'secure' => $https,
    'samesite' => 'Lax',
]);

function app_start_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

$scriptNameForSession = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
$isAdminSessionRequest = str_contains($scriptNameForSession, '/admin/') || basename($scriptNameForSession) === 'install.php';
$hasExistingSessionCookie = isset($_COOKIE[session_name()]);

// Data minimization: ordinary readers do not receive a PHP session cookie.
// Sessions are started only for the editorial system or when a valid session
// cookie already exists (for example, an authenticated editor viewing the site).
if ($isAdminSessionRequest || $hasExistingSessionCookie) {
    app_start_session();
}

$config = APP_INSTALLED ? require CONFIG_FILE : require ROOT_PATH . '/config.example.php';
date_default_timezone_set($config['site']['timezone'] ?? 'Europe/Moscow');

require_once ROOT_PATH . '/app/db.php';
require_once ROOT_PATH . '/app/helpers.php';

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    $nonce=csp_nonce();
    header("Content-Security-Policy: default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'; script-src 'self' 'nonce-".$nonce."' 'strict-dynamic' https://cdn.jsdelivr.net; script-src-attr 'none'; style-src 'self' 'nonce-".$nonce."' https://fonts.googleapis.com https://cdnjs.cloudflare.com; style-src-attr 'none'; font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: blob: https:; connect-src 'self' https://api.open-meteo.com; frame-src 'self' https://vk.com https://*.vk.com https://vkvideo.ru https://*.vkvideo.ru https://rutube.ru https://*.rutube.ru https://ok.ru https://*.ok.ru; media-src 'self' blob: https:; worker-src 'self' blob: https://cdn.jsdelivr.net; manifest-src 'self'; upgrade-insecure-requests");
}

if (APP_INSTALLED) {
    // Keep schema/seed work out of the hot request path. The old bootstrap
    // re-checked tables and indexes on every public request, which adds DB
    // metadata locks and becomes fragile under concurrent traffic.
    //
    // Bump this value whenever a deployment adds or changes an ensure_* migration.
    $runtimeSchemaVersion = '2026-10-03-security-v2';
    $runtimeSchemaKey = 'runtime_schema_version';

    if (setting($runtimeSchemaKey, '') !== $runtimeSchemaVersion) {
        $securityStorage = ROOT_PATH . '/storage/security';
        if (!is_dir($securityStorage)) {
            @mkdir($securityStorage, 0775, true);
        }

        $schemaLockPath = $securityStorage . '/schema-init.lock';
        $schemaLock = @fopen($schemaLockPath, 'c+');

        if ($schemaLock && @flock($schemaLock, LOCK_EX)) {
            try {
                // Another PHP worker may have completed initialization while
                // this request was waiting for the lock.
                if (setting($runtimeSchemaKey, '') !== $runtimeSchemaVersion) {
                    ensure_user_security_schema();
                    ensure_default_categories();
                    ensure_article_categories_schema();
                    ensure_article_location_schema();
                    ensure_article_images_schema();
                    ensure_article_reactions_schema();
                    ensure_newspapers_schema();
                    ensure_documents_schema();
                    ensure_main_menu_schema();
                    ensure_documents_main_menu_item();
                    ensure_homepage_right_blocks_schema();
                    ensure_right_blocks_area_schema();
                    ensure_photo_gallery_schema();
                    ensure_social_links_schema();
                    ensure_video_gallery_schema();
                    ensure_static_pages_schema();

                    save_setting($runtimeSchemaKey, $runtimeSchemaVersion);
                }
            } finally {
                @flock($schemaLock, LOCK_UN);
                @fclose($schemaLock);
            }
        } else {
            if (is_resource($schemaLock)) @fclose($schemaLock);
            // If the lock file cannot be created, fail explicitly rather than
            // racing database DDL from multiple concurrent requests.
            throw new RuntimeException('Не удалось получить блокировку инициализации схемы.');
        }
    }

    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $scriptBase = basename($scriptName);
    $isAdminRequest = str_contains($scriptName, '/admin/');

    if (
        maintenance_mode_enabled()
        && !$isAdminRequest
        && $scriptBase !== 'maintenance.php'
        && $scriptBase !== 'install.php'
        && !in_array($scriptBase, ['privacy.php','personal-data-consent.php','cookies.php'], true)
        && !admin_user()
    ) {
        http_response_code(503);
        header('Retry-After: 3600');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        require ROOT_PATH . '/maintenance.php';
        exit;
    }
}
