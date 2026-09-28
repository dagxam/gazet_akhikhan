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
    $text = mb_strtolower(trim($text), 'UTF-8');
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

function categories(): array
{
    if (!APP_INSTALLED) return [];
    return db()->query('SELECT * FROM categories WHERE is_active=1 ORDER BY sort_order, name')->fetchAll();
}

function latest_articles(int $limit = 8, ?int $excludeId = null): array
{
    if (!APP_INSTALLED) return [];
    $sql = "SELECT a.*, c.name category_name, c.slug category_slug
            FROM articles a LEFT JOIN categories c ON c.id=a.category_id
            WHERE a.status='published' AND (a.published_at IS NULL OR a.published_at<=NOW())";
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
                     AND (a.published_at IS NULL OR a.published_at<=NOW())
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
