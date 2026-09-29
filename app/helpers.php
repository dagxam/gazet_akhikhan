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

function admin_user(): ?array
{
    if (empty($_SESSION['admin_user']['id']) || !APP_INSTALLED) return null;

    static $loaded = false;
    static $cached = null;
    if ($loaded) return $cached;
    $loaded = true;

    try {
        $q = db()->prepare("SELECT id,name,email,role,status FROM users WHERE id=? LIMIT 1");
        $q->execute([(int)$_SESSION['admin_user']['id']]);
        $user = $q->fetch();

        if (!$user || ($user['status'] ?? '') !== 'active') {
            unset($_SESSION['admin_user']);
            $cached = null;
            return null;
        }

        unset($user['status']);
        $_SESSION['admin_user'] = $user;
        $cached = $user;
        return $cached;
    } catch (Throwable $e) {
        return $_SESSION['admin_user'] ?? null;
    }
}

function is_site_admin(): bool
{
    $user = admin_user();
    return $user && ($user['role'] ?? '') === 'admin';
}

function require_admin(): void
{
    if (!admin_user()) {
        header('Location: ' . base_url('admin/login.php'));
        exit;
    }
}

function require_site_admin(): void
{
    require_admin();
    if (!is_site_admin()) {
        http_response_code(403);
        exit('Недостаточно прав для этого действия.');
    }
}

function maintenance_mode_enabled(): bool
{
    return APP_INSTALLED && setting('maintenance_mode', '0') === '1';
}

function role_label(string $role): string
{
    return $role === 'admin' ? 'Администратор' : 'Редактор';
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


function ensure_newspapers_schema(): void
{
    if (!APP_INSTALLED) return;
    if (setting('schema_newspapers_v1', '') === '1') return;

    $pdo = db();
    $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS newspapers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            issue_number TEXT,
            issue_date TEXT NOT NULL,
            pdf_file TEXT NOT NULL,
            cover_image TEXT,
            status TEXT NOT NULL DEFAULT 'published' CHECK (status IN ('draft','published')),
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_newspapers_status_date ON newspapers(status,issue_date)');
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS newspapers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            issue_number VARCHAR(80) NULL,
            issue_date DATE NOT NULL,
            pdf_file VARCHAR(500) NOT NULL,
            cover_image VARCHAR(500) NULL,
            status ENUM('draft','published') NOT NULL DEFAULT 'published',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_newspapers_status_date (status,issue_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    save_setting('schema_newspapers_v1', '1');
}

function latest_newspaper(): ?array
{
    if (!APP_INSTALLED) return null;
    $q = db()->query("SELECT * FROM newspapers WHERE status='published' ORDER BY issue_date DESC,id DESC LIMIT 1");
    $row = $q->fetch();
    return $row ?: null;
}

function safe_delete_newspaper_upload(?string $relativePath): void
{
    if (!$relativePath || !str_starts_with($relativePath, 'uploads/newspapers/')) return;
    $full = ROOT_PATH . '/' . ltrim($relativePath, '/');
    if (is_file($full)) @unlink($full);
}

function handle_newspaper_pdf_upload(array $file, ?string $oldPdf = null, ?string $oldCover = null): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['pdf_file'=>$oldPdf, 'cover_image'=>$oldCover];
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Ошибка загрузки PDF.');
    }
    if (($file['size'] ?? 0) > 80 * 1024 * 1024) {
        throw new RuntimeException('PDF слишком большой. Максимум 80 МБ.');
    }

    $head = file_get_contents($file['tmp_name'], false, null, 0, 5);
    if ($head !== '%PDF-') {
        throw new RuntimeException('Загрузите файл газеты в формате PDF.');
    }

    $folder = 'uploads/newspapers/' . date('Y/m');
    $dir = ROOT_PATH . '/' . $folder;
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Не удалось создать папку для газет.');
    }

    $baseName = bin2hex(random_bytes(12));
    $pdfRelative = $folder . '/' . $baseName . '.pdf';
    $pdfFull = ROOT_PATH . '/' . $pdfRelative;
    if (!move_uploaded_file($file['tmp_name'], $pdfFull)) {
        throw new RuntimeException('Не удалось сохранить PDF.');
    }

    $coverRelative = null;

    if (class_exists('Imagick')) {
        try {
            $coverRelative = $folder . '/' . $baseName . '-cover.webp';
            $coverFull = ROOT_PATH . '/' . $coverRelative;

            $image = new Imagick();
            $image->setResolution(150, 150);
            $image->readImage($pdfFull . '[0]');
            $image->setImageBackgroundColor('white');
            if (method_exists($image, 'setImageAlphaChannel')) {
                $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
            }
            $image->setImageFormat('webp');
            $image->setImageCompressionQuality(88);
            $image->thumbnailImage(1000, 0);
            $image->writeImage($coverFull);
            $image->clear();
            $image->destroy();
        } catch (Throwable $e) {
            $coverRelative = null;
        }
    }

    if ($oldPdf && $oldPdf !== $pdfRelative) safe_delete_newspaper_upload($oldPdf);
    if ($oldCover && $oldCover !== $coverRelative) safe_delete_newspaper_upload($oldCover);

    return ['pdf_file'=>$pdfRelative, 'cover_image'=>$coverRelative];
}


function ensure_documents_schema(): void
{
    if (!APP_INSTALLED) return;
    if (setting('schema_documents_v1', '') === '1') return;

    $pdo = db();
    $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS documents (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT,
            document_date TEXT NOT NULL,
            file_path TEXT NOT NULL,
            file_ext TEXT NOT NULL,
            original_name TEXT,
            file_size INTEGER NOT NULL DEFAULT 0,
            status TEXT NOT NULL DEFAULT 'published' CHECK (status IN ('draft','published')),
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_documents_status_date ON documents(status,document_date)');
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS documents (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            document_date DATE NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_ext VARCHAR(12) NOT NULL,
            original_name VARCHAR(255) NULL,
            file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
            status ENUM('draft','published') NOT NULL DEFAULT 'published',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_documents_status_date (status,document_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    save_setting('schema_documents_v1', '1');
}

function latest_documents(int $limit = 8): array
{
    if (!APP_INSTALLED) return [];
    $sql = "SELECT * FROM documents WHERE status='published' ORDER BY document_date DESC,id DESC LIMIT " . max(1,$limit);
    return db()->query($sql)->fetchAll();
}

function document_format_label(string $ext): string
{
    $ext = strtolower($ext);
    return match($ext) {
        'doc','docx' => 'WORD',
        'xls','xlsx' => 'EXCEL',
        'ppt','pptx' => 'POWERPOINT',
        'pdf' => 'PDF',
        default => strtoupper($ext),
    };
}

function document_format_class(string $ext): string
{
    $ext = strtolower($ext);
    return match($ext) {
        'doc','docx' => 'word',
        'xls','xlsx' => 'excel',
        'ppt','pptx' => 'powerpoint',
        'pdf' => 'pdf',
        default => 'file',
    };
}

function human_file_size(int $bytes): string
{
    if ($bytes < 1024) return $bytes . ' Б';
    if ($bytes < 1024 * 1024) return number_format($bytes / 1024, 1, ',', ' ') . ' КБ';
    return number_format($bytes / (1024 * 1024), 1, ',', ' ') . ' МБ';
}

function safe_delete_document_upload(?string $relativePath): void
{
    if (!$relativePath || !str_starts_with($relativePath, 'uploads/documents/')) return;
    $full = ROOT_PATH . '/' . ltrim($relativePath,'/');
    if (is_file($full)) @unlink($full);
}

function handle_document_upload(array $file, ?string $oldPath = null, ?string $oldExt = null, ?string $oldName = null, int $oldSize = 0): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [
            'file_path'=>$oldPath,
            'file_ext'=>$oldExt,
            'original_name'=>$oldName,
            'file_size'=>$oldSize,
        ];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Ошибка загрузки документа.');
    }

    $size=(int)($file['size']??0);
    if ($size <= 0 || $size > 80 * 1024 * 1024) {
        throw new RuntimeException('Размер документа должен быть не более 80 МБ.');
    }

    $original=(string)($file['name']??'document');
    $ext=strtolower(pathinfo($original,PATHINFO_EXTENSION));
    $allowed=['pdf','doc','docx','xls','xlsx','ppt','pptx'];
    if (!in_array($ext,$allowed,true)) {
        throw new RuntimeException('Разрешены PDF, DOC, DOCX, XLS, XLSX, PPT и PPTX.');
    }

    $finfo=new finfo(FILEINFO_MIME_TYPE);
    $mime=(string)$finfo->file($file['tmp_name']);
    $acceptedMimes=[
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/zip',
        'application/x-zip',
        'application/x-zip-compressed',
        'application/octet-stream',
        'application/x-ole-storage',
        'application/CDFV2',
    ];
    if (!in_array($mime,$acceptedMimes,true)) {
        throw new RuntimeException('Формат файла не соответствует разрешённым документам.');
    }

    $folder='uploads/documents/'.date('Y/m');
    $dir=ROOT_PATH.'/'.$folder;
    if (!is_dir($dir) && !mkdir($dir,0775,true) && !is_dir($dir)) {
        throw new RuntimeException('Не удалось создать папку для документов.');
    }

    $name=bin2hex(random_bytes(12)).'.'.$ext;
    $relative=$folder.'/'.$name;
    if (!move_uploaded_file($file['tmp_name'],ROOT_PATH.'/'.$relative)) {
        throw new RuntimeException('Не удалось сохранить документ.');
    }

    if ($oldPath && $oldPath !== $relative) safe_delete_document_upload($oldPath);

    return [
        'file_path'=>$relative,
        'file_ext'=>$ext,
        'original_name'=>function_exists('mb_substr') ? mb_substr($original,0,255,'UTF-8') : substr($original,0,255),
        'file_size'=>$size,
    ];
}


function default_main_menu_seed(): array
{
    return [
        ['Главная', '/', 10],
        ['Новости', 'news.php', 20],
        ['Общество', 'category:obschestvo', 30],
        ['Экономика', 'category:ekonomika', 40],
        ['Культура', 'category:kultura', 50],
        ['Спорт', 'category:sport', 60],
        ['Люди', 'category:lyudi', 70],
        ['История', 'category:istoriya', 80],
        ['Фото', 'search.php?q=Фото', 90],
        ['Видео', 'search.php?q=Видео', 100],
    ];
}

function ensure_main_menu_schema(): void
{
    if (!APP_INSTALLED) return;
    if (setting('schema_main_menu_v1', '') === '1') return;

    $pdo = db();
    $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS main_menu_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            label TEXT NOT NULL,
            url TEXT NOT NULL,
            sort_order INTEGER NOT NULL DEFAULT 100,
            is_active INTEGER NOT NULL DEFAULT 1 CHECK (is_active IN (0,1)),
            open_new_tab INTEGER NOT NULL DEFAULT 0 CHECK (open_new_tab IN (0,1)),
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_main_menu_active_sort ON main_menu_items(is_active,sort_order)');
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS main_menu_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            label VARCHAR(120) NOT NULL,
            url VARCHAR(500) NOT NULL,
            sort_order INT NOT NULL DEFAULT 100,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            open_new_tab TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_main_menu_active_sort (is_active,sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    $count=(int)$pdo->query('SELECT COUNT(*) FROM main_menu_items')->fetchColumn();
    if($count===0){
        $insert=$pdo->prepare('INSERT INTO main_menu_items(label,url,sort_order,is_active,open_new_tab) VALUES(?,?,?,1,0)');
        foreach(default_main_menu_seed() as $item){
            $insert->execute([$item[0],$item[1],$item[2]]);
        }
    }

    save_setting('schema_main_menu_v1', '1');
}

function main_menu_items(bool $activeOnly = true): array
{
    if (!APP_INSTALLED) return [];
    $sql='SELECT * FROM main_menu_items';
    if($activeOnly) $sql .= ' WHERE is_active=1';
    $sql .= ' ORDER BY sort_order,id';
    return db()->query($sql)->fetchAll();
}

function main_menu_url(string $value): string
{
    $value=trim($value);
    if($value==='') return base_url();

    if(str_starts_with($value,'category:')){
        $slug=trim(substr($value,9));
        $q=db()->prepare('SELECT * FROM categories WHERE slug=? AND is_active=1 LIMIT 1');
        $q->execute([$slug]);
        $cat=$q->fetch();
        return $cat ? category_url($cat) : base_url('news.php');
    }

    if(preg_match('~^(https?://|mailto:|tel:)~i',$value)) return $value;
    if(str_starts_with($value,'#')) return $value;
    if($value==='/') return base_url();

    return base_url(ltrim($value,'/'));
}


function branding_asset(string $key, string $default): string
{
    $value = trim(setting($key, ''));
    return $value !== '' ? $value : $default;
}

function admin_theme_name(): string
{
    $theme = setting('admin_color_scheme', 'walnut');
    return in_array($theme, ['walnut','light','graphite','forest','burgundy','navy'], true) ? $theme : 'walnut';
}

function safe_delete_branding_asset(?string $relativePath): void
{
    if (!$relativePath || !str_starts_with($relativePath, 'uploads/branding/')) return;
    $full = ROOT_PATH . '/' . ltrim($relativePath, '/');
    if (is_file($full)) @unlink($full);
}

function handle_branding_asset_upload(array $file, string $kind, ?string $old = null): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return $old;
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Ошибка загрузки файла оформления.');
    }

    $size=(int)($file['size']??0);
    $max=$kind==='favicon' ? 2 * 1024 * 1024 : 10 * 1024 * 1024;
    if($size<=0 || $size>$max){
        throw new RuntimeException($kind==='favicon' ? 'Favicon должен быть не более 2 МБ.' : 'Логотип должен быть не более 10 МБ.');
    }

    $tmp=(string)($file['tmp_name']??'');
    $original=(string)($file['name']??'asset');
    $originalExt=strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $finfo=new finfo(FILEINFO_MIME_TYPE);
    $mime=(string)$finfo->file($tmp);

    $allowed=[
        'image/png'=>'png',
        'image/jpeg'=>'jpg',
        'image/webp'=>'webp',
    ];

    if($kind==='favicon'){
        $allowed['image/x-icon']='ico';
        $allowed['image/vnd.microsoft.icon']='ico';
        if($originalExt==='ico' && $mime==='application/octet-stream'){
            $head=file_get_contents($tmp,false,null,0,4);
            if($head==="\x00\x00\x01\x00") $allowed['application/octet-stream']='ico';
        }
    }

    if(!isset($allowed[$mime])){
        throw new RuntimeException($kind==='favicon'
            ? 'Для favicon разрешены ICO, PNG, JPG и WEBP.'
            : 'Для логотипа разрешены PNG, JPG и WEBP.');
    }

    $folder='uploads/branding/'.date('Y/m');
    $dir=ROOT_PATH.'/'.$folder;
    if(!is_dir($dir) && !mkdir($dir,0775,true) && !is_dir($dir)){
        throw new RuntimeException('Не удалось создать папку для файлов оформления.');
    }

    $name=$kind.'-'.bin2hex(random_bytes(10)).'.'.$allowed[$mime];
    $relative=$folder.'/'.$name;
    if(!move_uploaded_file($tmp,ROOT_PATH.'/'.$relative)){
        throw new RuntimeException('Не удалось сохранить файл оформления.');
    }

    if($old && $old!==$relative) safe_delete_branding_asset($old);
    return $relative;
}


function ensure_homepage_right_blocks_schema(): void
{
    if (!APP_INSTALLED) return;
    if (setting('schema_homepage_right_blocks_v1', '') === '1') return;

    $pdo = db();
    $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS homepage_right_blocks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            kicker TEXT,
            title TEXT NOT NULL,
            body TEXT,
            image TEXT,
            link_text TEXT,
            link_url TEXT,
            style TEXT NOT NULL DEFAULT 'light' CHECK (style IN ('light','accent','dark')),
            sort_order INTEGER NOT NULL DEFAULT 100,
            is_active INTEGER NOT NULL DEFAULT 1 CHECK (is_active IN (0,1)),
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_homepage_right_blocks_active_sort ON homepage_right_blocks(is_active,sort_order)');
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS homepage_right_blocks (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            kicker VARCHAR(100) NULL,
            title VARCHAR(255) NOT NULL,
            body TEXT NULL,
            image VARCHAR(500) NULL,
            link_text VARCHAR(100) NULL,
            link_url VARCHAR(500) NULL,
            style ENUM('light','accent','dark') NOT NULL DEFAULT 'light',
            sort_order INT NOT NULL DEFAULT 100,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_homepage_right_blocks_active_sort (is_active,sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    $count=(int)$pdo->query('SELECT COUNT(*) FROM homepage_right_blocks')->fetchColumn();
    if($count===0 && setting('right_block_enabled','1') === '1'){
        $q=$pdo->prepare('INSERT INTO homepage_right_blocks(kicker,title,body,image,link_text,link_url,style,sort_order,is_active) VALUES(?,?,?,?,?,?,?,?,1)');
        $q->execute([
            setting('right_block_kicker','От редакции'),
            setting('right_block_title','О районе — с уважением к людям и истории'),
            setting('right_block_text',setting('editor_note','Наша задача — рассказывать о важном для жителей района, сохранять память о прошлом и показывать людей, которые сегодня меняют родной край.')),
            setting('right_block_image','') ?: null,
            setting('right_block_link_text','') ?: null,
            setting('right_block_link_url','') ?: null,
            in_array(setting('right_block_style','light'),['light','accent','dark'],true) ? setting('right_block_style','light') : 'light',
            100
        ]);
    }

    save_setting('schema_homepage_right_blocks_v1', '1');
}

function homepage_right_blocks(bool $activeOnly = true): array
{
    if (!APP_INSTALLED) return [];
    $sql='SELECT * FROM homepage_right_blocks';
    if($activeOnly) $sql .= ' WHERE is_active=1';
    $sql .= ' ORDER BY sort_order,id';
    return db()->query($sql)->fetchAll();
}

function safe_delete_homepage_right_block_image(?string $relativePath): void
{
    if (!$relativePath || !str_starts_with($relativePath, 'uploads/')) return;
    $full=ROOT_PATH.'/'.ltrim($relativePath,'/');
    if(is_file($full)) @unlink($full);
}

function homepage_right_block_href(?string $url): string
{
    $url=trim((string)$url);
    if($url==='') return '';
    if(preg_match('~^(https?://|mailto:|tel:)~i',$url)) return $url;
    if(str_starts_with($url,'#')) return $url;
    return base_url(ltrim($url,'/'));
}


function ensure_photo_gallery_schema(): void
{
    if (!APP_INSTALLED) return;
    if (setting('schema_photo_gallery_v1', '') === '1') return;

    $pdo = db();
    $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS photo_albums (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT,
            album_date TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'published' CHECK (status IN ('draft','published')),
            sort_order INTEGER NOT NULL DEFAULT 100,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_photo_albums_status_date ON photo_albums(status,album_date)');

        $pdo->exec("CREATE TABLE IF NOT EXISTS photo_gallery_images (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            album_id INTEGER NOT NULL,
            image_path TEXT NOT NULL,
            caption TEXT,
            sort_order INTEGER NOT NULL DEFAULT 100,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (album_id) REFERENCES photo_albums(id) ON DELETE CASCADE
        )");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_photo_gallery_images_album_sort ON photo_gallery_images(album_id,sort_order,id)');
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS photo_albums (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            album_date DATE NOT NULL,
            status ENUM('draft','published') NOT NULL DEFAULT 'published',
            sort_order INT NOT NULL DEFAULT 100,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_photo_albums_status_date (status,album_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS photo_gallery_images (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            album_id INT UNSIGNED NOT NULL,
            image_path VARCHAR(500) NOT NULL,
            caption VARCHAR(500) NULL,
            sort_order INT NOT NULL DEFAULT 100,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_photo_gallery_images_album_sort (album_id,sort_order,id),
            CONSTRAINT fk_photo_gallery_images_album FOREIGN KEY (album_id) REFERENCES photo_albums(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    // The public "Фото" navigation item should open the gallery page.
    try {
        $q=$pdo->prepare("UPDATE main_menu_items SET url='gallery.php' WHERE label='Фото'");
        $q->execute();
        if($q->rowCount()===0){
            $check=$pdo->prepare("SELECT COUNT(*) FROM main_menu_items WHERE label='Фото'");
            $check->execute();
            if((int)$check->fetchColumn()===0){
                $insert=$pdo->prepare("INSERT INTO main_menu_items(label,url,sort_order,is_active,open_new_tab) VALUES('Фото','gallery.php',90,1,0)");
                $insert->execute();
            }
        }
    } catch (Throwable $e) {}

    save_setting('schema_photo_gallery_v1', '1');
}

function photo_albums(bool $publishedOnly = true): array
{
    if (!APP_INSTALLED) return [];

    $sql = "SELECT a.*,
        (SELECT COUNT(*) FROM photo_gallery_images p WHERE p.album_id=a.id) AS photo_count,
        (SELECT p.image_path FROM photo_gallery_images p WHERE p.album_id=a.id ORDER BY p.sort_order,p.id LIMIT 1) AS cover_image
        FROM photo_albums a";

    if($publishedOnly) $sql .= " WHERE a.status='published'";
    $sql .= " ORDER BY a.album_date DESC,a.sort_order,a.id DESC";

    return db()->query($sql)->fetchAll();
}

function photo_album(int $id, bool $publishedOnly = false): ?array
{
    if (!APP_INSTALLED || $id < 1) return null;

    $sql = "SELECT a.*,
        (SELECT COUNT(*) FROM photo_gallery_images p WHERE p.album_id=a.id) AS photo_count,
        (SELECT p.image_path FROM photo_gallery_images p WHERE p.album_id=a.id ORDER BY p.sort_order,p.id LIMIT 1) AS cover_image
        FROM photo_albums a WHERE a.id=?";
    if($publishedOnly) $sql .= " AND a.status='published'";
    $sql .= " LIMIT 1";

    $q=db()->prepare($sql);
    $q->execute([$id]);
    $row=$q->fetch();
    return $row ?: null;
}

function photo_album_images(int $albumId, int $limit = 0): array
{
    if (!APP_INSTALLED || $albumId < 1) return [];

    $sql='SELECT * FROM photo_gallery_images WHERE album_id=? ORDER BY sort_order,id';
    if($limit > 0) $sql .= ' LIMIT '.max(1,$limit);

    $q=db()->prepare($sql);
    $q->execute([$albumId]);
    return $q->fetchAll();
}

function latest_gallery_photos(int $limit = 6): array
{
    if (!APP_INSTALLED) return [];

    $limit=max(1,$limit);
    $sql="SELECT p.*,a.title AS album_title,a.album_date
          FROM photo_gallery_images p
          INNER JOIN photo_albums a ON a.id=p.album_id
          WHERE a.status='published'
          ORDER BY a.album_date DESC,a.id DESC,p.sort_order,p.id
          LIMIT ".$limit;

    return db()->query($sql)->fetchAll();
}

function latest_gallery_album(): ?array
{
    if (!APP_INSTALLED) return null;
    $q=db()->query("SELECT a.*,
        (SELECT COUNT(*) FROM photo_gallery_images p WHERE p.album_id=a.id) AS photo_count,
        (SELECT p.image_path FROM photo_gallery_images p WHERE p.album_id=a.id ORDER BY p.sort_order,p.id LIMIT 1) AS cover_image
        FROM photo_albums a
        WHERE a.status='published'
        ORDER BY a.album_date DESC,a.sort_order,a.id DESC
        LIMIT 1");
    $row=$q->fetch();
    return $row ?: null;
}

function safe_delete_gallery_image(?string $relativePath): void
{
    if (!$relativePath || !str_starts_with($relativePath, 'uploads/')) return;
    $full=ROOT_PATH.'/'.ltrim($relativePath,'/');
    if(is_file($full)) @unlink($full);
}
