<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_site_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('admin/'));
    exit;
}

verify_csrf();
save_setting('maintenance_mode', isset($_POST['maintenance_mode']) && $_POST['maintenance_mode'] === '1' ? '1' : '0');

$return = basename((string)($_POST['return'] ?? 'index.php'));
if (!preg_match('~^[a-z0-9._-]+\.php$~i', $return) || !is_file(__DIR__ . '/' . $return)) {
    $return = 'index.php';
}

header('Location: ' . base_url('admin/' . $return . '?maintenance_saved=1'));
exit;
