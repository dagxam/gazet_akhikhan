<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_url(string $path = ''): string
{
    global $config;
    $base = rtrim((string)($config['site']['base_url'] ?? ''), '/');
    return $base === '' ? '/' . ltrim($path, '/') : $base . '/' . ltrim($path, '/');
}

function slugify(string $text): string
{
    $map = ['а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh','з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya'];
    $text = function_exists('mb_strtolower') ? mb_strtolower(trim($text), 'UTF-8') : strtolower(trim($text));
    $text = strtr($text, $map);
    $text = preg_replace('~[^a-z0-9]+~', '-', $text) ?? '';
    return trim($text, '-') ?: 'material';
}

function setting(string $key, string $default = ''): string
{
    if (!APP_INSTALLED) return $default;
    try {
        $q = db()->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
        $q->execute([$key]);
        $v = $q->fetchColumn();
        return $v === false ? $default : (string)$v;
    } catch (Throwable $e) {
        return $default;
    }
}

function save_setting(string $key, string $value): void
{
    $check = db()->prepare('SELECT setting_key FROM settings WHERE setting_key=? LIMIT 1');
    $check->execute([$key]);

    if ($check->fetchColumn() !== false) {
        $q = db()->prepare('UPDATE settings SET setting_value=?, updated_at=CURRENT_TIMESTAMP WHERE setting_key=?');
        $q->execute([$value, $key]);
        return;
    }

    $q = db()->prepare('INSERT INTO settings(setting_key,setting_value) VALUES(?,?)');
    $q->execute([$key, $value]);
}

function ru_date(?string $date): string
{
    if (!$date) return '';
    $ts = strtotime($date);
    if (!$ts) return '';
    $months = [1=>'января',2=>'февраля',3=>'марта',4=>'апреля',5=>'мая',6=>'июня',7=>'июля',8=>'августа',9=>'сентября',10=>'октября',11=>'ноября',12=>'декабря'];
    return date('j', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

function current_date_ru(): string
{
    $week = [1=>'Понедельник',2=>'Вторник',3=>'Среда',4=>'Четверг',5=>'Пятница',6=>'Суббота',7=>'Воскресенье'];
    return $week[(int)date('N')] . ', ' . ru_date(date('Y-m-d'));
}



function default_category_seed(): array
{
    return [
        ['Главные новости', 'glavnye-novosti', 'Ключевые материалы редакции и главные события дня.', 5],
        ['Региональные новости', 'regionalnye-novosti', 'Новости Республики Дагестан, важные для жителей Унцукульского района.', 10],
        ['Новости района', 'novosti-rayona', 'События и новости Унцукульского района.', 20],
        ['Общество', 'obschestvo', 'Общественная жизнь района и социальные темы.', 30],
        ['Экономика', 'ekonomika', 'Экономика, развитие и проекты.', 40],
        ['Культура', 'kultura', 'Культура, традиции и народные промыслы.', 50],
        ['Спорт', 'sport', 'Спортивные события, команды и достижения.', 60],
        ['Криминал', 'kriminal', 'Происшествия, безопасность и официальная информация правоохранительных органов.', 70],
        ['Люди', 'lyudi', 'Истории жителей и земляков.', 80],
        ['История', 'istoriya', 'История района, память и памятные места.', 90],
    ];
}

function ensure_default_categories(): void
{
    if (!APP_INSTALLED) return;
    try {
        $rows = db()->query('SELECT slug FROM categories')->fetchAll();
        $existing = [];
        foreach ($rows as $row) $existing[(string)$row['slug']] = true;

        $insert = db()->prepare('INSERT INTO categories(name,slug,description,sort_order,is_active) VALUES(?,?,?,?,1)');
        foreach (default_category_seed() as $row) {
            [$name, $slug, $description, $sort] = $row;
            if (!isset($existing[$slug])) {
                $insert->execute([$name, $slug, $description, $sort]);
                $existing[$slug] = true;
            }
        }
    } catch (Throwable $e) {
    }
}

function category_id_by_slug(string $slug): ?int
{
    if (!APP_INSTALLED) return null;
    $q = db()->prepare('SELECT id FROM categories WHERE slug=? LIMIT 1');
    $q->execute([$slug]);
    $id = $q->fetchColumn();
    return $id === false ? null : (int)$id;
}

function ensure_article_categories_schema(): void
{
    if (!APP_INSTALLED) return;
    if (setting('schema_article_categories_v1', '') === '1') return;

    $pdo = db();
    $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS article_categories (
            article_id INTEGER NOT NULL,
            category_id INTEGER NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (article_id, category_id),
            FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        )");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_article_categories_category ON article_categories(category_id,article_id)');
        $pdo->exec('INSERT OR IGNORE INTO article_categories(article_id,category_id) SELECT id,category_id FROM articles WHERE category_id IS NOT NULL');
        $mainId = category_id_by_slug('glavnye-novosti');
        if ($mainId) {
            $q = $pdo->prepare('INSERT OR IGNORE INTO article_categories(article_id,category_id) SELECT id,? FROM articles WHERE category_id IS NULL');
            $q->execute([$mainId]);
            $pdo->prepare('UPDATE articles SET category_id=? WHERE category_id IS NULL')->execute([$mainId]);
        }
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS article_categories (
            article_id INT UNSIGNED NOT NULL,
            category_id INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (article_id,category_id),
            INDEX idx_article_categories_category (category_id,article_id),
            CONSTRAINT fk_article_categories_article FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
            CONSTRAINT fk_article_categories_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec('INSERT IGNORE INTO article_categories(article_id,category_id) SELECT id,category_id FROM articles WHERE category_id IS NOT NULL');
        $mainId = category_id_by_slug('glavnye-novosti');
        if ($mainId) {
            $q = $pdo->prepare('INSERT IGNORE INTO article_categories(article_id,category_id) SELECT id,? FROM articles WHERE category_id IS NULL');
            $q->execute([$mainId]);
            $pdo->prepare('UPDATE articles SET category_id=? WHERE category_id IS NULL')->execute([$mainId]);
        }
    }

    save_setting('schema_article_categories_v1', '1');
}

function article_category_ids(int $articleId): array
{
    if (!$articleId) return [];
    $q = db()->prepare('SELECT category_id FROM article_categories WHERE article_id=? ORDER BY category_id');
    $q->execute([$articleId]);
    return array_map('intval', array_column($q->fetchAll(), 'category_id'));
}

function article_categories(int $articleId): array
{
    if (!$articleId) return [];
    $q = db()->prepare('SELECT c.* FROM categories c INNER JOIN article_categories ac ON ac.category_id=c.id WHERE ac.article_id=? ORDER BY c.sort_order,c.name');
    $q->execute([$articleId]);
    return $q->fetchAll();
}

function set_article_categories(int $articleId, array $categoryIds): void
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $categoryIds), fn($id) => $id > 0)));
    if (!$ids) throw new RuntimeException('Выберите хотя бы одну рубрику.');

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $q = db()->prepare("SELECT id,slug FROM categories WHERE is_active=1 AND id IN ($placeholders) ORDER BY sort_order,name");
    $q->execute($ids);
    $valid = $q->fetchAll();
    if (!$valid) throw new RuntimeException('Выбранные рубрики не найдены.');

    $primaryId = (int)$valid[0]['id'];
    foreach ($valid as $row) {
        if ($row['slug'] !== 'glavnye-novosti') {
            $primaryId = (int)$row['id'];
            break;
        }
    }

    db()->prepare('DELETE FROM article_categories WHERE article_id=?')->execute([$articleId]);
    $insert = db()->prepare('INSERT INTO article_categories(article_id,category_id) VALUES(?,?)');
    foreach ($valid as $row) $insert->execute([$articleId, (int)$row['id']]);

    db()->prepare('UPDATE articles SET category_id=? WHERE id=?')->execute([$primaryId, $articleId]);
}

function find_or_create_category(string $name, string $description = '', int $sortOrder = 100): int
{
    $name = trim($name);
    if ($name === '') throw new RuntimeException('Введите название новой рубрики.');

    if (function_exists('mb_substr')) {
        $name = mb_substr($name, 0, 120, 'UTF-8');
        $description = mb_substr(trim($description), 0, 1500, 'UTF-8');
    } else {
        $name = substr($name, 0, 120);
        $description = substr(trim($description), 0, 1500);
    }

    $byName = db()->prepare('SELECT id FROM categories WHERE name=? LIMIT 1');
    $byName->execute([$name]);
    $id = $byName->fetchColumn();
    if ($id !== false) return (int)$id;

    $baseSlug = substr(slugify($name), 0, 150);
    $slug = $baseSlug;
    $bySlug = db()->prepare('SELECT id FROM categories WHERE slug=? LIMIT 1');

    for ($i = 1; $i < 100; $i++) {
        $bySlug->execute([$slug]);
        $slugId = $bySlug->fetchColumn();
        if ($slugId === false) break;
        if ($i === 1) return (int)$slugId;
        $slug = substr($baseSlug, 0, 145) . '-' . ($i + 1);
    }

    $q = db()->prepare('INSERT INTO categories(name,slug,description,sort_order,is_active) VALUES(?,?,?,?,1)');
    $q->execute([$name, $slug, $description !== '' ? $description : null, $sortOrder]);
    return (int)db()->lastInsertId();
}

function latest_articles_by_category_slug(string $slug, int $limit = 8): array
{
    if (!APP_INSTALLED) return [];
    $sql = "SELECT a.*, c.name category_name, c.slug category_slug
            FROM articles a
            INNER JOIN article_categories ac ON ac.article_id=a.id
            INNER JOIN categories c ON c.id=ac.category_id
            WHERE a.status='published'
              AND c.slug=?
              AND c.is_active=1
              AND (a.published_at IS NULL OR a.published_at<=CURRENT_TIMESTAMP)
            ORDER BY COALESCE(a.published_at,a.created_at) DESC
            LIMIT " . max(1, $limit);
    $q = db()->prepare($sql);
    $q->execute([$slug]);
    return $q->fetchAll();
}

function categories(): array
{
    if (!APP_INSTALLED) return [];
    return db()->query('SELECT * FROM categories WHERE is_active=1 ORDER BY sort_order, name')->fetchAll();
}

function latest_main_articles(int $limit = 5, ?int $excludeId = null): array
{
    if (!APP_INSTALLED) return [];
    $sql = "SELECT a.*, c.name category_name, c.slug category_slug
            FROM articles a
            INNER JOIN article_categories ac ON ac.article_id=a.id
            INNER JOIN categories c ON c.id=ac.category_id
            WHERE a.status='published'
              AND c.slug='glavnye-novosti'
              AND c.is_active=1
              AND (a.published_at IS NULL OR a.published_at<=CURRENT_TIMESTAMP)";
    $params = [];
    if ($excludeId) {
        $sql .= ' AND a.id<>?';
        $params[] = $excludeId;
    }
    $sql .= ' ORDER BY COALESCE(a.published_at,a.created_at) DESC LIMIT ' . max(1, $limit);
    $q = db()->prepare($sql);
    $q->execute($params);
    return $q->fetchAll();
}

function latest_articles(int $limit = 8, ?int $excludeId = null): array
{
    if (!APP_INSTALLED) return [];
    $sql = "SELECT a.*, c.name category_name, c.slug category_slug
            FROM articles a LEFT JOIN categories c ON c.id=a.category_id
            WHERE a.status='published' AND (a.published_at IS NULL OR a.published_at<=CURRENT_TIMESTAMP)";
    $params = [];
    if ($excludeId) { $sql .= ' AND a.id<>?'; $params[] = $excludeId; }
    $sql .= ' ORDER BY COALESCE(a.published_at,a.created_at) DESC LIMIT ' . max(1, $limit);
    $q = db()->prepare($sql);
    $q->execute($params);
    return $q->fetchAll();
}

function featured_article(): ?array
{
    if (!APP_INSTALLED) return null;
    $q = db()->query("SELECT a.*, c.name category_name, c.slug category_slug
                     FROM articles a LEFT JOIN categories c ON c.id=a.category_id
                     WHERE a.status='published' AND a.is_featured=1
                     AND (a.published_at IS NULL OR a.published_at<=CURRENT_TIMESTAMP)
                     ORDER BY COALESCE(a.published_at,a.created_at) DESC LIMIT 1");
    $row = $q->fetch();
    return $row ?: null;
}

function article_url(array $a): string { return base_url('article/' . $a['slug']); }
function category_url(array $c): string { return base_url('category/' . $c['slug']); }

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function verify_csrf(): void
{
    $token = (string)($_POST['csrf'] ?? '');
    if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('Сессия формы истекла. Обновите страницу и повторите.');
    }
}

function admin_user(): ?array { return $_SESSION['admin_user'] ?? null; }

function require_admin(): void
{
    if (!admin_user()) {
        header('Location: ' . base_url('admin/login.php'));
        exit;
    }
}

function handle_cover_upload(array $file, ?string $old = null): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return $old;
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('Ошибка загрузки изображения.');
    if (($file['size'] ?? 0) > 8 * 1024 * 1024) throw new RuntimeException('Изображение слишком большое. Максимум 8 МБ.');

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if (!isset($allowed[$mime])) throw new RuntimeException('Разрешены JPG, PNG и WEBP.');

    $folder = 'uploads/' . date('Y/m');
    $dir = ROOT_PATH . '/' . $folder;
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Не удалось создать папку uploads.');
    }
    $name = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('Не удалось сохранить изображение.');
    }
    return $folder . '/' . $name;
}
