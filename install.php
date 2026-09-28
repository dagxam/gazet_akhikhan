<?php
require __DIR__ . '/app/bootstrap.php';

if (APP_INSTALLED) {
    exit('<h2>AKHIKHAN.RU уже установлен.</h2><p><a href="/">Открыть сайт</a></p>');
}

$error = '';
$driver = $_POST['db_driver'] ?? 'sqlite';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $driver = in_array($_POST['db_driver'] ?? 'sqlite', ['sqlite','mysql'], true)
        ? $_POST['db_driver']
        : 'sqlite';

    $baseUrl = rtrim(trim($_POST['base_url'] ?? 'https://akhikhan.ru'), '/');
    $adminName = trim($_POST['admin_name'] ?? 'Администратор');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPass = (string)($_POST['admin_pass'] ?? '');

    try {
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL) || strlen($adminPass) < 8) {
            throw new RuntimeException('Укажите корректный e-mail. Пароль администратора — минимум 8 символов.');
        }

        if ($driver === 'sqlite') {
            if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
                throw new RuntimeException('На сервере не включено расширение PDO_SQLITE. В панели хостинга включите pdo_sqlite/sqlite3 либо выберите MySQL.');
            }

            $storageDir = __DIR__ . '/storage';
            if (!is_dir($storageDir) && !mkdir($storageDir, 0775, true) && !is_dir($storageDir)) {
                throw new RuntimeException('Не удалось создать папку storage для SQLite.');
            }
            if (!is_writable($storageDir)) {
                throw new RuntimeException('Папка storage недоступна для записи PHP. Выставьте права на запись для владельца сайта.');
            }

            $dbRelativePath = 'storage/akhikhan.sqlite';
            $dbPath = __DIR__ . '/' . $dbRelativePath;

            $pdo = new PDO('sqlite:' . $dbPath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $pdo->exec('PRAGMA foreign_keys = ON');
            $pdo->exec('PRAGMA busy_timeout = 5000');

            $schema = file_get_contents(__DIR__ . '/setup/schema.sqlite.sql');
            if ($schema === false) {
                throw new RuntimeException('Не найден SQLite schema.');
            }
            $pdo->exec($schema);

            $dbConfig = [
                'driver' => 'sqlite',
                'path' => $dbRelativePath,
            ];
        } else {
            $dbHost = trim($_POST['db_host'] ?? 'localhost');
            $dbPort = trim($_POST['db_port'] ?? '3306');
            $dbName = trim($_POST['db_name'] ?? '');
            $dbUser = trim($_POST['db_user'] ?? '');
            $dbPass = (string)($_POST['db_pass'] ?? '');

            if (!$dbHost || !$dbName || !$dbUser) {
                throw new RuntimeException('Для MySQL заполните хост, имя базы и пользователя.');
            }

            // Support the common host:port form as well as a separate port field.
            if (preg_match('~^([^:]+):(\\d+)$~', $dbHost, $m)) {
                $dbHost = $m[1];
                $dbPort = $m[2];
            }
            if (!ctype_digit($dbPort)) {
                throw new RuntimeException('Порт MySQL должен быть числом.');
            }

            $pdo = new PDO(
                'mysql:host=' . $dbHost . ';port=' . $dbPort . ';dbname=' . $dbName . ';charset=utf8mb4',
                $dbUser,
                $dbPass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

            $schema = file_get_contents(__DIR__ . '/setup/schema.sql');
            if ($schema === false) {
                throw new RuntimeException('Не найден MySQL schema.');
            }
            $pdo->exec($schema);

            $dbConfig = [
                'driver' => 'mysql',
                'host' => $dbHost,
                'port' => $dbPort,
                'name' => $dbName,
                'user' => $dbUser,
                'pass' => $dbPass,
                'charset' => 'utf8mb4',
            ];
        }

        $hash = password_hash($adminPass, PASSWORD_DEFAULT);

        $check = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
        $check->execute([$adminEmail]);
        $adminId = $check->fetchColumn();

        if ($adminId !== false) {
            $q = $pdo->prepare("UPDATE users SET name=?,password_hash=?,role='admin',status='active' WHERE id=?");
            $q->execute([$adminName, $hash, $adminId]);
        } else {
            $q = $pdo->prepare("INSERT INTO users(name,email,password_hash,role,status) VALUES(?,?,?,'admin','active')");
            $q->execute([$adminName, $adminEmail, $hash]);
        }

        $seed = default_category_seed();

        $existsCategory = $pdo->prepare('SELECT id FROM categories WHERE slug=? LIMIT 1');
        $insertCategory = $pdo->prepare('INSERT INTO categories(name,slug,description,sort_order,is_active) VALUES(?,?,?,?,1)');
        foreach ($seed as $row) {
            [$categoryName, $categorySlug, $categoryDescription, $categorySort] = $row;
            $existsCategory->execute([$categorySlug]);
            if ($existsCategory->fetchColumn() === false) {
                $insertCategory->execute([$categoryName, $categorySlug, $categoryDescription, $categorySort]);
            }
        }

        $settings = [
            'site_name'=>'AKHIKHAN.RU',
            'site_subtitle'=>'Местная газета для наших людей',
            'hero_kicker'=>'ГЛАВНАЯ НОВОСТЬ',
            'editor_note'=>'Мы верим, что местная газета — это не просто новости, а мост между людьми, поколениями и родным краем.',
            'footer_quote'=>'Сила народа — в его корнях, а будущее — в его людях',
        ];

        $settingExists = $pdo->prepare('SELECT setting_key FROM settings WHERE setting_key=? LIMIT 1');
        $settingInsert = $pdo->prepare('INSERT INTO settings(setting_key,setting_value) VALUES(?,?)');
        $settingUpdate = $pdo->prepare('UPDATE settings SET setting_value=?,updated_at=CURRENT_TIMESTAMP WHERE setting_key=?');

        foreach ($settings as $key => $value) {
            $settingExists->execute([$key]);
            if ($settingExists->fetchColumn() === false) {
                $settingInsert->execute([$key, $value]);
            } else {
                $settingUpdate->execute([$value, $key]);
            }
        }

        $configPhp = "<?php\nreturn " . var_export([
            'db' => $dbConfig,
            'site' => [
                'base_url' => $baseUrl,
                'timezone' => 'Europe/Moscow',
            ],
        ], true) . ";\n";

        if (file_put_contents(__DIR__ . '/config.php', $configPhp, LOCK_EX) === false) {
            throw new RuntimeException('База подготовлена, но config.php не записался. Проверьте права на корневую папку сайта.');
        }

        header('Location: ' . ($baseUrl ?: '') . '/admin/login.php?installed=1');
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$sqliteAvailable = in_array('sqlite', PDO::getAvailableDrivers(), true);
?><!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Установка AKHIKHAN.RU</title>
<style>
body{font-family:Arial,sans-serif;background:#f3efe6;color:#2e261d;margin:0}
.box{max-width:760px;margin:40px auto;background:#fffdf8;border:1px solid #d7c9b2;padding:30px;border-radius:14px}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
label{display:block;font-weight:700;margin-bottom:6px}
input,select{width:100%;box-sizing:border-box;padding:12px;border:1px solid #cdbb9d;border-radius:8px;background:#fff}
.full{grid-column:1/-1}
button{background:#6f4d25;color:#fff;border:0;padding:13px 20px;border-radius:8px;font-weight:700;cursor:pointer}
.err{background:#fde8e5;padding:12px;border-radius:8px;margin-bottom:16px}
.note{background:#f4efe6;border:1px solid #dccbad;padding:12px 14px;border-radius:8px;font-size:14px;line-height:1.45}
.ok{color:#256b3d;font-weight:700}
.bad{color:#a12c23;font-weight:700}
.mysql{display:none}
.mysql.active{display:contents}
@media(max-width:650px){.grid{grid-template-columns:1fr}.box{margin:16px}.full{grid-column:auto}.mysql.active{display:contents}}
</style>
</head>
<body>
<main class="box">
<h1>Установка AKHIKHAN.RU</h1>
<p>Для первого запуска можно использовать SQLite. Отдельный MySQL-сервер не нужен, а позже базу можно перенести.</p>

<?php if($error): ?><div class="err"><?=e($error)?></div><?php endif; ?>

<form method="post">
<input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
<div class="grid">
<div class="full">
<label>Тип базы данных</label>
<select name="db_driver" id="db_driver">
<option value="sqlite" <?=$driver==='sqlite'?'selected':''?>>SQLite — использовать сейчас</option>
<option value="mysql" <?=$driver==='mysql'?'selected':''?>>MySQL — подключить внешнюю базу</option>
</select>
</div>

<div class="full note">
<strong>SQLite:</strong> база будет храниться в защищённом файле <code>storage/akhikhan.sqlite</code>.
Статус PHP-драйвера:
<?php if($sqliteAvailable): ?><span class="ok">PDO_SQLITE доступен</span><?php else: ?><span class="bad">PDO_SQLITE не найден</span><?php endif; ?>.
</div>

<div class="mysql"><label>Хост MySQL</label><input name="db_host" value="<?=e($_POST['db_host'] ?? 'localhost')?>" placeholder="185.9.147.250"></div>
<div class="mysql"><label>Порт MySQL</label><input name="db_port" value="<?=e($_POST['db_port'] ?? '3306')?>" inputmode="numeric"></div>
<div class="mysql"><label>Имя БД</label><input name="db_name" value="<?=e($_POST['db_name'] ?? '')?>"></div>
<div class="mysql"><label>Пользователь БД</label><input name="db_user" value="<?=e($_POST['db_user'] ?? '')?>"></div>
<div class="mysql full"><label>Пароль БД</label><input type="password" name="db_pass"></div>

<div class="full"><label>Адрес сайта</label><input name="base_url" value="<?=e($_POST['base_url'] ?? 'https://akhikhan.ru')?>" required></div>
<div><label>Имя администратора</label><input name="admin_name" value="<?=e($_POST['admin_name'] ?? 'Администратор')?>" required></div>
<div><label>E-mail администратора</label><input type="email" name="admin_email" value="<?=e($_POST['admin_email'] ?? '')?>" required></div>
<div class="full"><label>Пароль администратора</label><input type="password" name="admin_pass" minlength="8" required></div>
<div class="full"><button>Установить сайт</button></div>
</div>
</form>
</main>

<script>
const select=document.getElementById('db_driver');
function toggleDb(){
  const mysql=select.value==='mysql';
  document.querySelectorAll('.mysql').forEach(el=>el.classList.toggle('active',mysql));
}
select.addEventListener('change',toggleDb);
toggleDb();
</script>
</body>
</html>
