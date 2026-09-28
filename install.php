<?php
require __DIR__ . '/app/bootstrap.php';

if (APP_INSTALLED) {
    exit('<h2>AKHIKHAN.RU уже установлен.</h2><p><a href="/">Открыть сайт</a></p>');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = (string)($_POST['db_pass'] ?? '');
    $baseUrl = rtrim(trim($_POST['base_url'] ?? ''), '/');
    $adminName = trim($_POST['admin_name'] ?? 'Администратор');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPass = (string)($_POST['admin_pass'] ?? '');

    try {
        if (!$dbName || !$dbUser || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL) || strlen($adminPass) < 8) {
            throw new RuntimeException('Заполните обязательные поля. Пароль администратора — минимум 8 символов.');
        }

        $pdo = new PDO('mysql:host=' . $dbHost . ';dbname=' . $dbName . ';charset=utf8mb4', $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec(file_get_contents(__DIR__ . '/setup/schema.sql'));

        $hash = password_hash($adminPass, PASSWORD_DEFAULT);
        $q = $pdo->prepare("INSERT INTO users(name,email,password_hash,role,status) VALUES(?,?,?,'admin','active')");
        $q->execute([$adminName, $adminEmail, $hash]);

        $seed = [
            ['Общество','obschestvo',10],['Экономика','ekonomika',20],['Культура','kultura',30],
            ['Спорт','sport',40],['Люди','lyudi',50],['История','istoriya',60]
        ];
        $q = $pdo->prepare('INSERT IGNORE INTO categories(name,slug,sort_order,is_active) VALUES(?,?,?,1)');
        foreach ($seed as $row) $q->execute($row);

        $settings = [
            'site_name'=>'AKHIKHAN.RU',
            'site_subtitle'=>'Местная газета для наших людей',
            'hero_kicker'=>'ГЛАВНАЯ НОВОСТЬ',
            'editor_note'=>'Мы верим, что местная газета — это не просто новости, а мост между людьми, поколениями и родным краем.',
            'footer_quote'=>'Сила народа — в его корнях, а будущее — в его людях'
        ];
        $q = $pdo->prepare('INSERT INTO settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
        foreach ($settings as $k=>$v) $q->execute([$k,$v]);

        $configPhp = "<?php
return " . var_export([
            'db'=>['host'=>$dbHost,'port'=>'3306','name'=>$dbName,'user'=>$dbUser,'pass'=>$dbPass,'charset'=>'utf8mb4'],
            'site'=>['base_url'=>$baseUrl,'timezone'=>'Europe/Moscow'],
        ], true) . ";
";

        if (file_put_contents(__DIR__ . '/config.php', $configPhp, LOCK_EX) === false) {
            throw new RuntimeException('База создана, но config.php не записался. Проверьте права на папку.');
        }

        header('Location: ' . ($baseUrl ?: '') . '/admin/login.php?installed=1');
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?><!doctype html>
<html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Установка AKHIKHAN.RU</title>
<style>body{font-family:Arial,sans-serif;background:#f3efe6;color:#2e261d;margin:0}.box{max-width:720px;margin:50px auto;background:#fffdf8;border:1px solid #d7c9b2;padding:30px;border-radius:14px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}label{display:block;font-weight:700;margin-bottom:6px}input{width:100%;box-sizing:border-box;padding:12px;border:1px solid #cdbb9d;border-radius:8px}.full{grid-column:1/-1}button{background:#6f4d25;color:#fff;border:0;padding:13px 20px;border-radius:8px;font-weight:700;cursor:pointer}.err{background:#fde8e5;padding:12px;border-radius:8px;margin-bottom:16px}@media(max-width:650px){.grid{grid-template-columns:1fr}.box{margin:16px}}</style>
</head><body><main class="box"><h1>Установка AKHIKHAN.RU</h1><p>Подключим базу данных и создадим первого администратора.</p>
<?php if($error): ?><div class="err"><?=e($error)?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
<div class="grid">
<div><label>Хост БД</label><input name="db_host" value="localhost" required></div>
<div><label>Имя БД</label><input name="db_name" required></div>
<div><label>Пользователь БД</label><input name="db_user" required></div>
<div><label>Пароль БД</label><input type="password" name="db_pass"></div>
<div class="full"><label>Адрес сайта</label><input name="base_url" placeholder="https://akhikhan.ru"></div>
<div><label>Имя администратора</label><input name="admin_name" value="Администратор" required></div>
<div><label>E-mail администратора</label><input type="email" name="admin_email" required></div>
<div class="full"><label>Пароль администратора</label><input type="password" name="admin_pass" minlength="8" required></div>
<div class="full"><button>Установить сайт</button></div>
</div></form></main></body></html>
