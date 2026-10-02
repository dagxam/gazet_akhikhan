<?php
require dirname(__DIR__) . '/app/bootstrap.php';

header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow, noarchive');

if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){
    http_response_code(405);
    header('Allow: POST');
    exit('Метод не поддерживается.');
}

require_admin();
verify_csrf();

$_SESSION=[];
if(ini_get('session.use_cookies')){
    $p=session_get_cookie_params();
    setcookie(session_name(),'',[
        'expires'=>time()-42000,
        'path'=>$p['path'] ?: '/',
        'domain'=>$p['domain'] ?: '',
        'secure'=>(bool)$p['secure'],
        'httponly'=>(bool)$p['httponly'],
        'samesite'=>$p['samesite'] ?: 'Lax',
    ]);
}
session_destroy();

header('Clear-Site-Data: "cache", "cookies"');
header('Location: '.base_url('admin/login.php'));
exit;
