<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}



function sanitize_rich_text(?string $html): string
{
    $html=trim((string)$html);
    if($html==='') return '';

    // Legacy plain text stays plain; rendering helper will preserve its line breaks.
    if(!preg_match('~<\/?[a-z][^>]*>~i',$html)){
        return $html;
    }

    if(!class_exists('DOMDocument')){
        // Safe fallback: formatting is discarded rather than trusting unsanitized attributes.
        return rich_text_plain($html);
    }

    $allowedTags=['p','br','strong','b','em','i','u','s','strike','ul','ol','li','blockquote','h2','h3','h4','a','span','font','div'];
    $allowedFonts=['Manrope','Montserrat','PT Serif','Rubik','Noto Sans','Noto Serif','Georgia','Arial','sans-serif','serif'];
    $fontSizes=['1','2','3','4','5','6','7'];

    $doc=new DOMDocument('1.0','UTF-8');
    libxml_use_internal_errors(true);
    $wrapped='<!doctype html><html><head><meta charset="utf-8"></head><body><div id="rich-root">'.$html.'</div></body></html>';
    $doc->loadHTML($wrapped,LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $root=$doc->getElementById('rich-root');
    if(!$root) return rich_text_plain($html);

    $cleanStyle=function(string $style) use ($allowedFonts): string {
        $out=[];
        foreach(explode(';',$style) as $rule){
            if(!str_contains($rule,':')) continue;
            [$prop,$value]=array_map('trim',explode(':',$rule,2));
            $prop=strtolower($prop);
            if($prop==='color' || $prop==='background-color'){
                if(preg_match('~^#[0-9a-f]{3,8}$~i',$value) || preg_match('~^rgba?\([0-9., %]+\)$~i',$value)){
                    $out[]=$prop.':'.$value;
                }
                continue;
            }
            if($prop==='text-align' && in_array(strtolower($value),['left','center','right','justify'],true)){
                $out[]='text-align:'.strtolower($value);
                continue;
            }
            if($prop==='font-size' && preg_match('~^(?:0\.[6-9]|1(?:\.\d)?|2(?:\.0)?)rem$~',$value)){
                $out[]='font-size:'.$value;
                continue;
            }
            if($prop==='font-family'){
                $font=trim($value," \t\n\r\0\x0B\"'");
                if(in_array($font,$allowedFonts,true)){
                    $out[]='font-family:\''.$font.'\'';
                }
            }
        }
        return implode(';',$out);
    };

    $walk=function(DOMNode $node) use (&$walk,$allowedTags,$allowedFonts,$fontSizes,$cleanStyle): void {
        for($child=$node->firstChild;$child;){
            $next=$child->nextSibling;
            if($child instanceof DOMElement){
                $tag=strtolower($child->tagName);
                if(!in_array($tag,$allowedTags,true)){
                    while($child->firstChild){
                        $node->insertBefore($child->firstChild,$child);
                    }
                    $node->removeChild($child);
                    $child=$next;
                    continue;
                }

                $attrs=[];
                foreach(iterator_to_array($child->attributes) as $attr){
                    $attrs[]=$attr->name;
                }
                foreach($attrs as $name){
                    $value=$child->getAttribute($name);
                    $keep=false;

                    if($tag==='a' && $name==='href'){
                        $v=trim($value);
                        $keep=(bool)preg_match('~^(https?://|mailto:|tel:|/|#)~i',$v);
                    }elseif($tag==='a' && in_array($name,['target','rel'],true)){
                        $keep=true;
                    }elseif(in_array($tag,['span','p','div','h2','h3','h4','blockquote','li'],true) && $name==='style'){
                        $safe=$cleanStyle($value);
                        if($safe!==''){
                            $child->setAttribute('style',$safe);
                            $keep=true;
                        }
                    }elseif($tag==='font' && $name==='face'){
                        $keep=in_array(trim($value),$allowedFonts,true);
                    }elseif($tag==='font' && $name==='color'){
                        $keep=(bool)preg_match('~^#[0-9a-f]{3,8}$~i',trim($value));
                    }elseif($tag==='font' && $name==='size'){
                        $keep=in_array(trim($value),$fontSizes,true);
                    }

                    if(!$keep) $child->removeAttribute($name);
                }

                if($tag==='a'){
                    $child->setAttribute('rel','noopener noreferrer');
                    if(preg_match('~^https?://~i',$child->getAttribute('href'))){
                        $child->setAttribute('target','_blank');
                    }
                }

                $walk($child);
            }elseif($child->nodeType===XML_COMMENT_NODE){
                $node->removeChild($child);
            }
            $child=$next;
        }
    };

    $walk($root);

    $out='';
    foreach(iterator_to_array($root->childNodes) as $child){
        $out.=$doc->saveHTML($child);
    }
    return trim($out);
}

function rich_text_plain(?string $value): string
{
    $value=(string)$value;
    if($value==='') return '';

    $value=preg_replace('~<\s*br\s*/?\s*>~i',"\n",$value) ?? $value;
    $value=preg_replace('~</\s*(p|div|h[1-6]|li|blockquote)\s*>~i',"\n",$value) ?? $value;
    $value=strip_tags($value);
    $value=html_entity_decode($value,ENT_QUOTES|ENT_HTML5,'UTF-8');
    $value=preg_replace("~[ \t]+~u",' ',$value) ?? $value;
    $value=preg_replace("~\n{3,}~u","\n\n",$value) ?? $value;
    return trim($value);
}

function rich_text_html(?string $value): string
{
    $value=trim((string)$value);
    if($value==='') return '';

    if(!preg_match('~<\/?[a-z][^>]*>~i',$value)){
        return nl2br(e($value));
    }
    return sanitize_rich_text($value);
}

function rich_text_excerpt(?string $value, int $limit = 220): string
{
    $plain=rich_text_plain($value);
    if($plain==='') return '';
    if(function_exists('mb_strlen') && mb_strlen($plain,'UTF-8')>$limit){
        return rtrim(mb_substr($plain,0,$limit,'UTF-8')).'…';
    }
    if(!function_exists('mb_strlen') && strlen($plain)>$limit){
        return rtrim(substr($plain,0,$limit)).'…';
    }
    return $plain;
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

    // v2 intentionally runs once even when the old v1 flag exists. During a
    // database migration the flag could be copied before every junction row
    // was present, leaving homepage category blocks empty.
    if (setting('schema_article_categories_v2_repaired', '') === '1') return;

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
            $q = $pdo->prepare('INSERT OR IGNORE INTO article_categories(article_id,category_id)
                SELECT a.id,? FROM articles a
                WHERE a.category_id IS NULL
                  AND NOT EXISTS (SELECT 1 FROM article_categories ac WHERE ac.article_id=a.id)');
            $q->execute([$mainId]);
            $pdo->prepare('UPDATE articles SET category_id=? WHERE category_id IS NULL
                AND EXISTS (SELECT 1 FROM article_categories ac WHERE ac.article_id=articles.id AND ac.category_id=?)')
                ->execute([$mainId,$mainId]);
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
            $q = $pdo->prepare('INSERT IGNORE INTO article_categories(article_id,category_id)
                SELECT a.id,? FROM articles a
                WHERE a.category_id IS NULL
                  AND NOT EXISTS (SELECT 1 FROM article_categories ac WHERE ac.article_id=a.id)');
            $q->execute([$mainId]);
            $pdo->prepare('UPDATE articles a
                INNER JOIN article_categories ac ON ac.article_id=a.id AND ac.category_id=?
                SET a.category_id=?
                WHERE a.category_id IS NULL')
                ->execute([$mainId,$mainId]);
        }
    }

    save_setting('schema_article_categories_v1', '1');
    save_setting('schema_article_categories_v2_repaired', '1');
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
    $sql = "SELECT a.*, c.name category_name, c.slug category_slug, u.name author_name
            FROM articles a
            INNER JOIN categories c ON c.slug=? AND c.is_active=1
            LEFT JOIN users u ON u.id=a.author_id
            WHERE a.status='published'
              AND (a.published_at IS NULL OR a.published_at<=CURRENT_TIMESTAMP)
              AND (
                a.category_id=c.id
                OR EXISTS (
                  SELECT 1 FROM article_categories ac
                  WHERE ac.article_id=a.id AND ac.category_id=c.id
                )
              )
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
    $sql = "SELECT a.*, c.name category_name, c.slug category_slug, u.name author_name
            FROM articles a
            INNER JOIN categories c ON c.slug='glavnye-novosti' AND c.is_active=1
            LEFT JOIN users u ON u.id=a.author_id
            WHERE a.status='published'
              AND (a.published_at IS NULL OR a.published_at<=CURRENT_TIMESTAMP)
              AND (
                a.category_id=c.id
                OR EXISTS (
                  SELECT 1 FROM article_categories ac
                  WHERE ac.article_id=a.id AND ac.category_id=c.id
                )
              )";
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
    $sql = "SELECT a.*, c.name category_name, c.slug category_slug, u.name author_name
            FROM articles a
            LEFT JOIN categories c ON c.id=a.category_id
            LEFT JOIN users u ON u.id=a.author_id
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
    $q = db()->query("SELECT a.*, c.name category_name, c.slug category_slug, u.name author_name
                     FROM articles a
                     LEFT JOIN categories c ON c.id=a.category_id
                     LEFT JOIN users u ON u.id=a.author_id
                     WHERE a.status='published' AND a.is_featured=1
                     AND (a.published_at IS NULL OR a.published_at<=CURRENT_TIMESTAMP)
                     ORDER BY COALESCE(a.published_at,a.created_at) DESC LIMIT 1");
    $row = $q->fetch();
    return $row ?: null;
}

function article_url(array $a): string { return base_url('article/' . $a['slug']); }
function category_url(array $c): string { return base_url('category/' . $c['slug']); }

function security_client_ip(): string
{
    $ip=trim((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    if($ip==='') $ip='unknown';
    return substr($ip,0,80);
}

function security_login_bucket_path(): string
{
    $dir=ROOT_PATH.'/storage/security';
    if(!is_dir($dir)){
        @mkdir($dir,0775,true);
    }
    return $dir.'/login-'.hash('sha256',security_client_ip()).'.json';
}

function security_login_bucket(bool $recordFailure = false): array
{
    $now=time();
    $window=15*60;
    $limit=8;
    $lockFor=15*60;
    $file=security_login_bucket_path();

    $state=['failures'=>[],'blocked_until'=>0];
    $fp=@fopen($file,'c+');
    if(!$fp){
        return ['blocked'=>false,'remaining'=>0,'count'=>0];
    }

    if(!@flock($fp,LOCK_EX)){
        fclose($fp);
        return ['blocked'=>false,'remaining'=>0,'count'=>0];
    }

    rewind($fp);
    $raw=stream_get_contents($fp);
    if(is_string($raw) && trim($raw)!==''){
        $decoded=json_decode($raw,true);
        if(is_array($decoded)) $state=array_merge($state,$decoded);
    }

    $failures=[];
    foreach((array)($state['failures']??[]) as $ts){
        $ts=(int)$ts;
        if($ts>=$now-$window && $ts<=$now+60) $failures[]=$ts;
    }
    $blockedUntil=(int)($state['blocked_until']??0);
    if($blockedUntil<=$now) $blockedUntil=0;

    if($recordFailure && $blockedUntil===0){
        $failures[]=$now;
        if(count($failures)>=$limit){
            $blockedUntil=$now+$lockFor;
        }
    }

    $state=['failures'=>$failures,'blocked_until'=>$blockedUntil];
    rewind($fp);
    ftruncate($fp,0);
    fwrite($fp,json_encode($state,JSON_UNESCAPED_SLASHES));
    fflush($fp);
    flock($fp,LOCK_UN);
    fclose($fp);

    return [
        'blocked'=>$blockedUntil>$now,
        'remaining'=>max(0,$blockedUntil-$now),
        'count'=>count($failures),
    ];
}

function login_rate_limit_status(): array
{
    return security_login_bucket(false);
}

function login_rate_limit_failure(): array
{
    return security_login_bucket(true);
}

function login_rate_limit_clear(): void
{
    $file=security_login_bucket_path();
    if(is_file($file)) @unlink($file);
}

function csrf_token(): string
{
    if (function_exists('app_start_session')) app_start_session();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function verify_csrf(): void
{
    if (function_exists('app_start_session')) app_start_session();
    $token = (string)($_POST['csrf'] ?? '');
    if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('Сессия формы истекла. Обновите страницу и повторите.');
    }
}

function admin_user(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        if (!isset($_COOKIE[session_name()])) return null;
        if (function_exists('app_start_session')) app_start_session();
    }
    if (empty($_SESSION['admin_user']['id']) || !APP_INSTALLED) return null;

    $now=time();
    $loginAt=(int)($_SESSION['admin_login_at'] ?? $now);
    $lastActivity=(int)($_SESSION['admin_last_activity'] ?? $now);

    // Close abandoned admin sessions: 4 hours idle or 24 hours absolute lifetime.
    if(($now-$lastActivity)>4*3600 || ($now-$loginAt)>24*3600){
        unset(
            $_SESSION['admin_user'],
            $_SESSION['admin_login_at'],
            $_SESSION['admin_last_activity'],
            $_SESSION['admin_last_regen']
        );
        if(session_status()===PHP_SESSION_ACTIVE) @session_regenerate_id(true);
        return null;
    }

    $_SESSION['admin_login_at']=$loginAt;
    $_SESSION['admin_last_activity']=$now;

    $lastRegen=(int)($_SESSION['admin_last_regen'] ?? 0);
    if($lastRegen===0 || ($now-$lastRegen)>30*60){
        if(session_status()===PHP_SESSION_ACTIVE && !headers_sent()){
            @session_regenerate_id(true);
        }
        $_SESSION['admin_last_regen']=$now;
    }

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
    header('Cache-Control: no-store, no-cache, must-revalidate, private');
    header('Pragma: no-cache');
    header('X-Robots-Tag: noindex, nofollow, noarchive');

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

function all_published_documents(): array
{
    if (!APP_INSTALLED) return [];
    return db()->query("SELECT * FROM documents WHERE status='published' ORDER BY document_date DESC,id DESC")->fetchAll();
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

function ensure_documents_main_menu_item(): void
{
    if (!APP_INSTALLED) return;
    if (setting('schema_main_menu_documents_v1','') === '1') return;

    $pdo=db();
    $check=$pdo->prepare("SELECT id FROM main_menu_items WHERE LOWER(TRIM(url)) IN ('documents.php','/documents.php','documents') LIMIT 1");
    $check->execute();
    if(!$check->fetchColumn()){
        $maxOrder=(int)$pdo->query('SELECT COALESCE(MAX(sort_order),0) FROM main_menu_items')->fetchColumn();
        $insert=$pdo->prepare('INSERT INTO main_menu_items(label,url,sort_order,is_active,open_new_tab) VALUES(?,?,?,?,?)');
        $insert->execute(['Документы','documents.php',$maxOrder+10,1,0]);
    }

    save_setting('schema_main_menu_documents_v1','1');
}

function main_menu_items(bool $activeOnly = true): array
{
    if (!APP_INSTALLED) return [];
    $sql='SELECT * FROM main_menu_items';
    if($activeOnly) $sql .= ' WHERE is_active=1';
    $sql .= ' ORDER BY sort_order,id';
    return db()->query($sql)->fetchAll();
}

function save_main_menu_order(array $submittedIds): void
{
    if (!APP_INSTALLED) return;

    $rows=main_menu_items(false);
    if(!$rows) return;

    $existing=[];
    foreach($rows as $row) $existing[(int)$row['id']]=true;

    $ordered=[];
    foreach($submittedIds as $rawId){
        $id=(int)$rawId;
        if($id>0 && isset($existing[$id]) && !in_array($id,$ordered,true)) $ordered[]=$id;
    }
    foreach($rows as $row){
        $id=(int)$row['id'];
        if(!in_array($id,$ordered,true)) $ordered[]=$id;
    }

    $pdo=db();
    $started=!$pdo->inTransaction();
    if($started) $pdo->beginTransaction();
    try{
        $q=$pdo->prepare('UPDATE main_menu_items SET sort_order=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
        foreach($ordered as $index=>$id){
            $q->execute([($index+1)*10,$id]);
        }
        if($started) $pdo->commit();
    }catch(Throwable $e){
        if($started && $pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
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
    if (function_exists('right_blocks')) return right_blocks('home',$activeOnly);
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


function social_service_catalog(): array
{
    return [
        'vk'       => ['name'=>'ВКонтакте',      'icon'=>'fa-brands fa-vk'],
        'ok'       => ['name'=>'Одноклассники',  'icon'=>'fa-brands fa-odnoklassniki'],
        'max'      => ['name'=>'MAX',            'icon'=>'fa-solid fa-message','asset'=>'assets/img/social-max.svg','asset_black'=>'assets/img/social-max-black.svg','asset_white'=>'assets/img/social-max-white.svg'],
        'telegram' => ['name'=>'Telegram',       'icon'=>'fa-brands fa-telegram'],
        'dzen'     => ['name'=>'Дзен',           'icon'=>'fa-solid fa-circle-nodes'],
        'rutube'   => ['name'=>'Rutube',         'icon'=>'fa-solid fa-play'],
        'mail'     => ['name'=>'Электронная почта','icon'=>'fa-solid fa-envelope'],
        'custom'   => ['name'=>'Другая ссылка',  'icon'=>'fa-solid fa-link'],
    ];
}

function social_service_name(string $service): string
{
    $catalog=social_service_catalog();
    return $catalog[$service]['name'] ?? 'Ссылка';
}

function social_service_icon(string $service): string
{
    $catalog=social_service_catalog();
    return $catalog[$service]['icon'] ?? 'fa-solid fa-link';
}

function social_service_asset(string $service, string $variant = 'color'): string
{
    $catalog=social_service_catalog();
    $meta=$catalog[$service] ?? [];
    $key=match($variant){
        'black'=>'asset_black',
        'white'=>'asset_white',
        default=>'asset',
    };
    $asset=trim((string)($meta[$key] ?? ''));
    if($asset==='') $asset=trim((string)($meta['asset'] ?? ''));
    return $asset;
}

function normalize_social_url(string $service, string $url): string
{
    $url=trim($url);
    if($url==='') return '';

    if($service==='mail'){
        if(str_starts_with(strtolower($url),'mailto:')) return $url;
        if(filter_var($url,FILTER_VALIDATE_EMAIL)) return 'mailto:'.$url;
        throw new RuntimeException('Для почты укажите корректный e-mail.');
    }

    if(!preg_match('~^https?://~i',$url)){
        $url='https://'.$url;
    }
    if(!filter_var($url,FILTER_VALIDATE_URL)){
        throw new RuntimeException('Укажите корректную ссылку на социальную сеть.');
    }
    return $url;
}

function ensure_social_links_schema(): void
{
    if (!APP_INSTALLED) return;
    if (setting('schema_social_links_v1','') === '1') return;

    $pdo=db();
    $driver=(string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if($driver==='sqlite'){
        $pdo->exec("CREATE TABLE IF NOT EXISTS social_links (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            service TEXT NOT NULL,
            label TEXT,
            url TEXT NOT NULL,
            sort_order INTEGER NOT NULL DEFAULT 100,
            is_active INTEGER NOT NULL DEFAULT 1 CHECK (is_active IN (0,1)),
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_social_links_active_sort ON social_links(is_active,sort_order,id)');
    }else{
        $pdo->exec("CREATE TABLE IF NOT EXISTS social_links (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            service VARCHAR(40) NOT NULL,
            label VARCHAR(120) NULL,
            url VARCHAR(500) NOT NULL,
            sort_order INT NOT NULL DEFAULT 100,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_social_links_active_sort (is_active,sort_order,id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    $count=(int)$pdo->query('SELECT COUNT(*) FROM social_links')->fetchColumn();
    if($count===0){
        $seed=[];
        $vk=trim(setting('topbar_vk_url',''));
        $ok=trim(setting('topbar_ok_url',''));
        $mail=trim(setting('topbar_email',''));
        if($vk!=='') $seed[]=['vk','ВКонтакте',$vk,10];
        if($ok!=='') $seed[]=['ok','Одноклассники',$ok,20];
        if($mail!=='') $seed[]=['mail','Почта редакции',str_starts_with(strtolower($mail),'mailto:')?$mail:'mailto:'.$mail,30];

        if($seed){
            $q=$pdo->prepare('INSERT INTO social_links(service,label,url,sort_order,is_active) VALUES(?,?,?,?,1)');
            foreach($seed as $row) $q->execute($row);
        }
    }

    save_setting('schema_social_links_v1','1');
}

function social_links(bool $activeOnly = true): array
{
    if(!APP_INSTALLED) return [];
    $sql='SELECT * FROM social_links';
    if($activeOnly) $sql .= ' WHERE is_active=1';
    $sql .= ' ORDER BY sort_order,id';
    return db()->query($sql)->fetchAll();
}


function ensure_video_gallery_schema(): void
{
    if (!APP_INSTALLED) return;
    if (setting('schema_video_gallery_v1','') === '1') return;

    $pdo=db();
    $driver=(string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if($driver==='sqlite'){
        $pdo->exec("CREATE TABLE IF NOT EXISTS video_gallery (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT,
            video_date TEXT NOT NULL,
            source_type TEXT NOT NULL DEFAULT 'external' CHECK (source_type IN ('external','local')),
            provider TEXT CHECK (provider IN ('vk','rutube','ok','local')),
            source_url TEXT,
            video_file TEXT,
            cover_image TEXT,
            status TEXT NOT NULL DEFAULT 'published' CHECK (status IN ('draft','published')),
            sort_order INTEGER NOT NULL DEFAULT 100,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_video_gallery_status_date ON video_gallery(status,video_date,sort_order,id)');
    }else{
        $pdo->exec("CREATE TABLE IF NOT EXISTS video_gallery (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            video_date DATE NOT NULL,
            source_type ENUM('external','local') NOT NULL DEFAULT 'external',
            provider ENUM('vk','rutube','ok','local') NULL,
            source_url VARCHAR(1000) NULL,
            video_file VARCHAR(500) NULL,
            cover_image VARCHAR(500) NULL,
            status ENUM('draft','published') NOT NULL DEFAULT 'published',
            sort_order INT NOT NULL DEFAULT 100,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_video_gallery_status_date (status,video_date,sort_order,id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    // Existing public "Видео" menu item should open the new video gallery.
    try{
        $q=$pdo->prepare("UPDATE main_menu_items SET url='videos.php' WHERE label='Видео'");
        $q->execute();
        if($q->rowCount()===0){
            $check=$pdo->prepare("SELECT COUNT(*) FROM main_menu_items WHERE label='Видео'");
            $check->execute();
            if((int)$check->fetchColumn()===0){
                $insert=$pdo->prepare("INSERT INTO main_menu_items(label,url,sort_order,is_active,open_new_tab) VALUES('Видео','videos.php',100,1,0)");
                $insert->execute();
            }
        }
    }catch(Throwable $e){}

    save_setting('schema_video_gallery_v1','1');
}

function video_gallery_items(bool $publishedOnly = true): array
{
    if(!APP_INSTALLED) return [];
    $sql='SELECT * FROM video_gallery';
    if($publishedOnly) $sql .= " WHERE status='published'";
    $sql .= ' ORDER BY video_date DESC,sort_order,id DESC';
    return db()->query($sql)->fetchAll();
}

function video_gallery_item(int $id, bool $publishedOnly = false): ?array
{
    if(!APP_INSTALLED || $id<1) return null;
    $sql='SELECT * FROM video_gallery WHERE id=?';
    if($publishedOnly) $sql .= " AND status='published'";
    $sql .= ' LIMIT 1';
    $q=db()->prepare($sql);
    $q->execute([$id]);
    $row=$q->fetch();
    return $row ?: null;
}

function detect_video_provider(string $url): ?string
{
    $host=strtolower((string)(parse_url(trim($url),PHP_URL_HOST) ?? ''));
    $host=preg_replace('~^www\.~','',$host) ?? $host;
    if($host==='vk.com' || str_ends_with($host,'.vk.com') || $host==='vkvideo.ru' || str_ends_with($host,'.vkvideo.ru')) return 'vk';
    if($host==='rutube.ru' || str_ends_with($host,'.rutube.ru')) return 'rutube';
    if($host==='ok.ru' || str_ends_with($host,'.ok.ru')) return 'ok';
    return null;
}

function normalize_video_source_url(string $url): array
{
    $url=trim($url);
    if($url==='' || !filter_var($url,FILTER_VALIDATE_URL)){
        throw new RuntimeException('Укажите корректную ссылку на видео.');
    }

    $provider=detect_video_provider($url);
    if(!$provider){
        throw new RuntimeException('Разрешены только ссылки VK, Rutube и Одноклассники.');
    }

    $probe=[
        'source_type'=>'external',
        'provider'=>$provider,
        'source_url'=>$url,
    ];
    if(video_embed_url($probe)===''){
        throw new RuntimeException('Не удалось распознать ссылку на видео. Скопируйте обычную ссылку на конкретное видео VK, Rutube или Одноклассников.');
    }

    return [$provider,$url];
}

function video_embed_url(array $video): string
{
    if(($video['source_type']??'')!=='external') return '';

    $url=trim((string)($video['source_url']??''));
    $provider=(string)($video['provider']??detect_video_provider($url)??'');

    if($provider==='rutube'){
        if(preg_match('~rutube\.ru/(?:video|shorts)/([a-zA-Z0-9_-]+)~i',$url,$m)){
            return 'https://rutube.ru/play/embed/'.$m[1];
        }
        if(preg_match('~rutube\.ru/play/embed/([a-zA-Z0-9_-]+)~i',$url,$m)){
            return 'https://rutube.ru/play/embed/'.$m[1];
        }
    }

    if($provider==='ok'){
        if(preg_match('~ok\.ru/(?:video|videoembed)/(\d+)~i',$url,$m)){
            return 'https://ok.ru/videoembed/'.$m[1];
        }
    }

    if($provider==='vk'){
        $decoded=urldecode($url);
        if(preg_match('~video(-?\d+)_(\d+)~i',$decoded,$m)){
            return 'https://vk.com/video_ext.php?oid='.$m[1].'&id='.$m[2].'&hd=2';
        }
        if(str_contains($url,'video_ext.php')){
            return $url;
        }
    }

    return '';
}

function video_provider_label(?string $provider): string
{
    return match($provider){
        'vk'=>'VK',
        'rutube'=>'Rutube',
        'ok'=>'Одноклассники',
        'local'=>'Видео',
        default=>'Видео',
    };
}

function handle_video_upload(array $file, ?string $oldPath = null): ?string
{
    if(($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) return $oldPath;

    $error=(int)($file['error']??UPLOAD_ERR_OK);
    if($error===UPLOAD_ERR_INI_SIZE || $error===UPLOAD_ERR_FORM_SIZE){
        throw new RuntimeException('Видеофайл превышает ограничение загрузки сервера.');
    }
    if($error!==UPLOAD_ERR_OK){
        throw new RuntimeException('Ошибка загрузки видеофайла.');
    }

    $size=(int)($file['size']??0);
    if($size<=0 || $size>300*1024*1024){
        throw new RuntimeException('Размер видео должен быть не более 300 МБ.');
    }

    $tmp=(string)($file['tmp_name']??'');
    $original=(string)($file['name']??'video');
    $ext=strtolower(pathinfo($original,PATHINFO_EXTENSION));

    $finfo=new finfo(FILEINFO_MIME_TYPE);
    $mime=(string)$finfo->file($tmp);
    $allowed=[
        'video/mp4'=>'mp4',
        'video/webm'=>'webm',
        'video/ogg'=>'ogv',
        'video/quicktime'=>'mov',
        'video/x-m4v'=>'m4v',
    ];

    if(!isset($allowed[$mime]) || !in_array($ext,['mp4','webm','ogv','ogg','mov','m4v'],true)){
        throw new RuntimeException('Разрешены видео MP4, WEBM, OGV/OGG, MOV и M4V.');
    }

    $folder='uploads/videos/'.date('Y/m');
    $dir=ROOT_PATH.'/'.$folder;
    if(!is_dir($dir) && !mkdir($dir,0775,true) && !is_dir($dir)){
        throw new RuntimeException('Не удалось создать папку для видео.');
    }

    $finalExt=$allowed[$mime];
    $name=bin2hex(random_bytes(12)).'.'.$finalExt;
    $relative=$folder.'/'.$name;

    if(!move_uploaded_file($tmp,ROOT_PATH.'/'.$relative)){
        throw new RuntimeException('Не удалось сохранить видеофайл.');
    }

    if($oldPath && $oldPath!==$relative) safe_delete_video_upload($oldPath);
    return $relative;
}

function safe_delete_video_upload(?string $relativePath): void
{
    if(!$relativePath || !str_starts_with($relativePath,'uploads/videos/')) return;
    $full=ROOT_PATH.'/'.ltrim($relativePath,'/');
    if(is_file($full)) @unlink($full);
}

function safe_delete_video_cover(?string $relativePath): void
{
    if(!$relativePath || !str_starts_with($relativePath,'uploads/')) return;
    $full=ROOT_PATH.'/'.ltrim($relativePath,'/');
    if(is_file($full)) @unlink($full);
}


function ensure_right_blocks_area_schema(): void
{
    if (!APP_INSTALLED) return;

    $pdo=db();
    $driver=(string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    // Do not trust only the migration flag: verify the real database schema.
    // This makes the manager self-healing after interrupted/partial deployments.
    if($driver==='sqlite'){
        $pdo->exec("CREATE TABLE IF NOT EXISTS homepage_right_blocks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            kicker TEXT,
            title TEXT NOT NULL,
            body TEXT,
            image TEXT,
            link_text TEXT,
            link_url TEXT,
            style TEXT NOT NULL DEFAULT 'light',
            sort_order INTEGER NOT NULL DEFAULT 100,
            is_active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");

        $cols=$pdo->query("PRAGMA table_info(homepage_right_blocks)")->fetchAll();
        $hasArea=false;
        foreach($cols as $col){
            if(($col['name']??'')==='area'){ $hasArea=true; break; }
        }
        if(!$hasArea){
            $pdo->exec("ALTER TABLE homepage_right_blocks ADD COLUMN area TEXT NOT NULL DEFAULT 'home'");
        }
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_homepage_right_blocks_area_active_sort ON homepage_right_blocks(area,is_active,sort_order,id)");
    }else{
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

        $q=$pdo->query("SHOW COLUMNS FROM homepage_right_blocks LIKE 'area'");
        if(!$q->fetch()){
            $pdo->exec("ALTER TABLE homepage_right_blocks ADD COLUMN area ENUM('home','pages') NOT NULL DEFAULT 'home' AFTER id");
        }

        try{
            $idx=$pdo->query("SHOW INDEX FROM homepage_right_blocks WHERE Key_name='idx_homepage_right_blocks_area_active_sort'");
            if(!$idx->fetch()){
                $pdo->exec("CREATE INDEX idx_homepage_right_blocks_area_active_sort ON homepage_right_blocks(area,is_active,sort_order,id)");
            }
        }catch(Throwable $e){
            error_log('[right blocks index] '.$e->getMessage());
        }
    }

    // Old blocks always belong to the home page.
    try{
        $pdo->exec("UPDATE homepage_right_blocks SET area='home' WHERE area IS NULL OR area=''");
    }catch(Throwable $e){}

    save_setting('schema_right_blocks_area_v1','1');
}

function right_blocks(string $area = 'home', bool $activeOnly = true): array
{
    if (!APP_INSTALLED) return [];
    $area=in_array($area,['home','pages'],true)?$area:'home';

    try{
        ensure_right_blocks_area_schema();
        $sql='SELECT * FROM homepage_right_blocks WHERE area=?';
        if($activeOnly) $sql .= ' AND is_active=1';
        $sql .= ' ORDER BY sort_order,id';
        $q=db()->prepare($sql);
        $q->execute([$area]);
        return $q->fetchAll();
    }catch(Throwable $e){
        error_log('[right blocks read] '.$e->getMessage());

        // Backward-compatible fallback for an old table without the area column.
        if($area!=='home') return [];
        try{
            $sql='SELECT * FROM homepage_right_blocks';
            if($activeOnly) $sql .= ' WHERE is_active=1';
            $sql .= ' ORDER BY sort_order,id';
            return db()->query($sql)->fetchAll();
        }catch(Throwable $fallback){
            error_log('[right blocks fallback] '.$fallback->getMessage());
            return [];
        }
    }
}

function page_right_blocks(bool $activeOnly = true): array
{
    return right_blocks('pages',$activeOnly);
}

function ensure_static_pages_schema(): void
{
    if (!APP_INSTALLED) return;
    if (setting('schema_static_pages_v1','') === '1') return;

    $pdo=db();
    $driver=(string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if($driver==='sqlite'){
        $pdo->exec("CREATE TABLE IF NOT EXISTS static_pages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            excerpt TEXT,
            content TEXT NOT NULL,
            cover_image TEXT,
            status TEXT NOT NULL DEFAULT 'draft' CHECK (status IN ('draft','published')),
            menu_item_id INTEGER,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_static_pages_status_title ON static_pages(status,title)");
    }else{
        $pdo->exec("CREATE TABLE IF NOT EXISTS static_pages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            excerpt TEXT NULL,
            content LONGTEXT NOT NULL,
            cover_image VARCHAR(500) NULL,
            status ENUM('draft','published') NOT NULL DEFAULT 'draft',
            menu_item_id INT UNSIGNED NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_static_pages_status_title (status,title)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    save_setting('schema_static_pages_v1','1');
}

function static_pages(bool $publishedOnly = false): array
{
    if(!APP_INSTALLED) return [];
    $sql='SELECT * FROM static_pages';
    if($publishedOnly) $sql .= " WHERE status='published'";
    $sql .= ' ORDER BY title,id';
    return db()->query($sql)->fetchAll();
}

function static_page(int $id, bool $publishedOnly = false): ?array
{
    if(!APP_INSTALLED || $id<1) return null;
    $sql='SELECT * FROM static_pages WHERE id=?';
    if($publishedOnly) $sql .= " AND status='published'";
    $sql .= ' LIMIT 1';
    $q=db()->prepare($sql);
    $q->execute([$id]);
    $row=$q->fetch();
    return $row ?: null;
}

function static_page_by_slug(string $slug, bool $publishedOnly = true): ?array
{
    if(!APP_INSTALLED) return null;
    $sql='SELECT * FROM static_pages WHERE slug=?';
    if($publishedOnly) $sql .= " AND status='published'";
    $sql .= ' LIMIT 1';
    $q=db()->prepare($sql);
    $q->execute([$slug]);
    $row=$q->fetch();
    return $row ?: null;
}

function static_page_url(array $page): string
{
    return base_url('page/'.rawurlencode((string)$page['slug']));
}

function safe_delete_static_page_cover(?string $relativePath): void
{
    if(!$relativePath || !str_starts_with($relativePath,'uploads/')) return;
    $full=ROOT_PATH.'/'.ltrim($relativePath,'/');
    if(is_file($full)) @unlink($full);
}

function sync_static_page_menu(int $pageId, string $title, string $slug, bool $addToMenu, string $menuLabel = '', int $menuOrder = 100): ?int
{
    $page=static_page($pageId,false);
    if(!$page) return null;

    $menuId=(int)($page['menu_item_id']??0);
    $url='page/'.$slug;
    $label=trim($menuLabel)!=='' ? trim($menuLabel) : $title;
    $menuOrder=max(-9999,min(9999,$menuOrder));
    $menuActive=(($page['status']??'draft')==='published') ? 1 : 0;

    if(!$addToMenu){
        if($menuId>0){
            db()->prepare('DELETE FROM main_menu_items WHERE id=?')->execute([$menuId]);
        }
        db()->prepare('UPDATE static_pages SET menu_item_id=NULL WHERE id=?')->execute([$pageId]);
        return null;
    }

    $exists=false;
    if($menuId>0){
        $q=db()->prepare('SELECT id FROM main_menu_items WHERE id=? LIMIT 1');
        $q->execute([$menuId]);
        $exists=(bool)$q->fetchColumn();
    }

    if($exists){
        db()->prepare('UPDATE main_menu_items SET label=?,url=?,sort_order=?,is_active=?,open_new_tab=0,updated_at=CURRENT_TIMESTAMP WHERE id=?')
            ->execute([$label,$url,$menuOrder,$menuActive,$menuId]);
    }else{
        $q=db()->prepare('INSERT INTO main_menu_items(label,url,sort_order,is_active,open_new_tab) VALUES(?,?,?,?,0)');
        $q->execute([$label,$url,$menuOrder,$menuActive]);
        $menuId=(int)db()->lastInsertId();
        db()->prepare('UPDATE static_pages SET menu_item_id=? WHERE id=?')->execute([$menuId,$pageId]);
    }

    return $menuId;
}


function ensure_article_location_schema(): void
{
    if(!APP_INSTALLED) return;
    if(setting('schema_article_location_v1','')==='1') return;

    $pdo=db();
    $driver=(string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if($driver==='sqlite'){
        $cols=$pdo->query("PRAGMA table_info(articles)")->fetchAll();
        $names=[];
        foreach($cols as $col) $names[(string)($col['name']??'')]=true;

        if(!isset($names['location_region'])){
            $pdo->exec("ALTER TABLE articles ADD COLUMN location_region TEXT NULL");
        }
        if(!isset($names['location_city'])){
            $pdo->exec("ALTER TABLE articles ADD COLUMN location_city TEXT NULL");
        }
    }else{
        $q=$pdo->query("SHOW COLUMNS FROM articles LIKE 'location_region'");
        if(!$q->fetch()){
            $pdo->exec("ALTER TABLE articles ADD COLUMN location_region VARCHAR(160) NULL AFTER excerpt");
        }
        $q=$pdo->query("SHOW COLUMNS FROM articles LIKE 'location_city'");
        if(!$q->fetch()){
            $pdo->exec("ALTER TABLE articles ADD COLUMN location_city VARCHAR(160) NULL AFTER location_region");
        }
    }

    save_setting('schema_article_location_v1','1');
}


function ensure_article_images_schema(): void
{
    if(!APP_INSTALLED) return;
    if(setting('schema_article_images_v1','')==='1') return;

    $pdo=db();
    $driver=(string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if($driver==='sqlite'){
        $pdo->exec("CREATE TABLE IF NOT EXISTS article_images (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            article_id INTEGER NOT NULL,
            image_path TEXT NOT NULL,
            caption TEXT,
            sort_order INTEGER NOT NULL DEFAULT 100,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(article_id) REFERENCES articles(id) ON DELETE CASCADE
        )");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_article_images_article ON article_images(article_id,sort_order,id)');
    }else{
        $pdo->exec("CREATE TABLE IF NOT EXISTS article_images (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            article_id INT UNSIGNED NOT NULL,
            image_path VARCHAR(500) NOT NULL,
            caption VARCHAR(500) NULL,
            sort_order INT NOT NULL DEFAULT 100,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_article_images_article (article_id,sort_order,id),
            CONSTRAINT fk_article_images_article FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    save_setting('schema_article_images_v1','1');
}

function article_images(int $articleId): array
{
    if(!APP_INSTALLED || $articleId<1) return [];
    $q=db()->prepare('SELECT * FROM article_images WHERE article_id=? ORDER BY sort_order,id');
    $q->execute([$articleId]);
    return $q->fetchAll();
}

function safe_delete_article_image(?string $relativePath): void
{
    if(!$relativePath || !str_starts_with($relativePath,'uploads/')) return;
    $full=ROOT_PATH.'/'.ltrim($relativePath,'/');
    if(is_file($full)) @unlink($full);
}

function handle_article_image_uploads(int $articleId, array $files, int $maxTotal = 12): int
{
    if($articleId<1 || empty($files['name']) || !is_array($files['name'])) return 0;

    $existing=article_images($articleId);
    $remaining=max(0,$maxTotal-count($existing));
    if($remaining===0) return 0;

    $insert=db()->prepare('INSERT INTO article_images(article_id,image_path,caption,sort_order) VALUES(?,?,NULL,?)');
    $added=0;
    $count=count($files['name']);

    for($i=0;$i<$count && $added<$remaining;$i++){
        if(($files['error'][$i]??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) continue;
        $single=[
            'name'=>$files['name'][$i]??'image',
            'type'=>$files['type'][$i]??'',
            'tmp_name'=>$files['tmp_name'][$i]??'',
            'error'=>$files['error'][$i]??UPLOAD_ERR_NO_FILE,
            'size'=>$files['size'][$i]??0,
        ];
        $path=handle_cover_upload($single,null);
        if(!$path) continue;
        $insert->execute([$articleId,$path,100+$i]);
        $added++;
    }
    return $added;
}

function remove_article_images(int $articleId, array $ids): void
{
    $ids=array_values(array_unique(array_filter(array_map('intval',$ids),fn($id)=>$id>0)));
    if(!$ids) return;

    $select=db()->prepare('SELECT id,image_path FROM article_images WHERE article_id=? AND id=? LIMIT 1');
    $delete=db()->prepare('DELETE FROM article_images WHERE article_id=? AND id=?');
    foreach($ids as $id){
        $select->execute([$articleId,$id]);
        $row=$select->fetch();
        if(!$row) continue;
        safe_delete_article_image($row['image_path']??null);
        $delete->execute([$articleId,$id]);
    }
}

function ensure_article_reactions_schema(): void
{
    if(!APP_INSTALLED) return;
    if(setting('schema_article_reactions_v1','')==='1') return;

    $pdo=db();
    $driver=(string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if($driver==='sqlite'){
        $pdo->exec("CREATE TABLE IF NOT EXISTS article_reactions (
            article_id INTEGER NOT NULL,
            voter_hash TEXT NOT NULL,
            reaction TEXT NOT NULL CHECK (reaction IN ('like','dislike')),
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(article_id,voter_hash),
            FOREIGN KEY(article_id) REFERENCES articles(id) ON DELETE CASCADE
        )");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_article_reactions_score ON article_reactions(article_id,reaction)');
    }else{
        $pdo->exec("CREATE TABLE IF NOT EXISTS article_reactions (
            article_id INT UNSIGNED NOT NULL,
            voter_hash CHAR(64) NOT NULL,
            reaction ENUM('like','dislike') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(article_id,voter_hash),
            INDEX idx_article_reactions_score (article_id,reaction),
            CONSTRAINT fk_article_reactions_article FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    save_setting('schema_article_reactions_v1','1');
}

function article_reaction_counts(int $articleId): array
{
    if($articleId<1) return ['likes'=>0,'dislikes'=>0,'score'=>0];
    $q=db()->prepare("SELECT
        SUM(CASE WHEN reaction='like' THEN 1 ELSE 0 END) likes,
        SUM(CASE WHEN reaction='dislike' THEN 1 ELSE 0 END) dislikes
        FROM article_reactions WHERE article_id=?");
    $q->execute([$articleId]);
    $row=$q->fetch() ?: [];
    $likes=(int)($row['likes']??0);
    $dislikes=(int)($row['dislikes']??0);
    return ['likes'=>$likes,'dislikes'=>$dislikes,'score'=>$likes-$dislikes];
}

function update_article_reaction(int $articleId, string $reaction, string $token): array
{
    $reaction=in_array($reaction,['like','dislike','none'],true)?$reaction:'';
    $token=trim($token);
    if($articleId<1 || $reaction==='' || strlen($token)<16 || strlen($token)>160){
        throw new RuntimeException('Некорректная оценка новости.');
    }

    $q=db()->prepare("SELECT id FROM articles WHERE id=? AND status='published' LIMIT 1");
    $q->execute([$articleId]);
    if(!$q->fetchColumn()) throw new RuntimeException('Новость не найдена.');

    $hash=hash('sha256',$token);
    $pdo=db();
    if($reaction==='none'){
        $pdo->prepare('DELETE FROM article_reactions WHERE article_id=? AND voter_hash=?')->execute([$articleId,$hash]);
    }elseif((string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME)==='sqlite'){
        $q=$pdo->prepare("INSERT INTO article_reactions(article_id,voter_hash,reaction,updated_at)
            VALUES(?,?,?,CURRENT_TIMESTAMP)
            ON CONFLICT(article_id,voter_hash) DO UPDATE SET reaction=excluded.reaction,updated_at=CURRENT_TIMESTAMP");
        $q->execute([$articleId,$hash,$reaction]);
    }else{
        $q=$pdo->prepare("INSERT INTO article_reactions(article_id,voter_hash,reaction)
            VALUES(?,?,?)
            ON DUPLICATE KEY UPDATE reaction=VALUES(reaction),updated_at=CURRENT_TIMESTAMP");
        $q->execute([$articleId,$hash,$reaction]);
    }

    return article_reaction_counts($articleId);
}

function top_rated_articles(int $limit = 4): array
{
    if(!APP_INSTALLED) return [];
    $limit=max(1,min(12,$limit));
    $sql="SELECT a.*,c.name category_name,c.slug category_slug,u.name author_name,
        COALESCE(r.likes,0) likes,COALESCE(r.dislikes,0) dislikes,
        COALESCE(r.likes,0)-COALESCE(r.dislikes,0) reaction_score
        FROM articles a
        LEFT JOIN categories c ON c.id=a.category_id
        LEFT JOIN users u ON u.id=a.author_id
        LEFT JOIN (
          SELECT article_id,
            SUM(CASE WHEN reaction='like' THEN 1 ELSE 0 END) likes,
            SUM(CASE WHEN reaction='dislike' THEN 1 ELSE 0 END) dislikes
          FROM article_reactions
          GROUP BY article_id
        ) r ON r.article_id=a.id
        WHERE a.status='published'
          AND (a.published_at IS NULL OR a.published_at<=CURRENT_TIMESTAMP)
        ORDER BY reaction_score DESC,likes DESC,a.views DESC,COALESCE(a.published_at,a.created_at) DESC
        LIMIT ".$limit;
    return db()->query($sql)->fetchAll();
}

function admin_paginate_array(array $items, int $perPage = 12, string $pageParam = 'page'): array
{
    $perPage=max(1,min(100,$perPage));
    $total=count($items);
    $totalPages=max(1,(int)ceil($total/$perPage));
    $page=max(1,(int)($_GET[$pageParam]??1));
    if($page>$totalPages) $page=$totalPages;
    $offset=($page-1)*$perPage;

    return [
        'items'=>array_slice($items,$offset,$perPage),
        'page'=>$page,
        'per_page'=>$perPage,
        'total'=>$total,
        'total_pages'=>$totalPages,
        'offset'=>$offset,
        'start'=>$total ? $offset+1 : 0,
        'end'=>min($offset+$perPage,$total),
        'param'=>$pageParam,
    ];
}

function admin_pagination_pages(int $page, int $totalPages): array
{
    if($totalPages<=9) return range(1,max(1,$totalPages));

    $pages=[1];
    $from=max(2,$page-2);
    $to=min($totalPages-1,$page+2);
    if($from>2) $pages[]='…';
    for($i=$from;$i<=$to;$i++) $pages[]=$i;
    if($to<$totalPages-1) $pages[]='…';
    $pages[]=$totalPages;
    return $pages;
}

function admin_pagination_url(string $path, int $page, array $query = [], string $pageParam = 'page'): string
{
    $query[$pageParam]=max(1,$page);
    return base_url(ltrim($path,'/').'?'.http_build_query($query));
}

function render_admin_pagination(string $path, int $page, int $totalPages, array $query = [], string $pageParam = 'page', string $label = 'Страницы'): void
{
    if($totalPages<=1) return;

    echo '<nav class="admin-pagination" aria-label="'.e($label).'">';
    if($page>1){
        echo '<a class="admin-page-arrow" href="'.e(admin_pagination_url($path,$page-1,$query,$pageParam)).'" aria-label="Предыдущая страница">←</a>';
    }

    foreach(admin_pagination_pages($page,$totalPages) as $p){
        if($p==='…'){
            echo '<span class="admin-page-gap">…</span>';
            continue;
        }
        $active=((int)$p===$page) ? ' is-active' : '';
        echo '<a class="'.$active.'" href="'.e(admin_pagination_url($path,(int)$p,$query,$pageParam)).'">'.e((string)$p).'</a>';
    }

    if($page<$totalPages){
        echo '<a class="admin-page-arrow" href="'.e(admin_pagination_url($path,$page+1,$query,$pageParam)).'" aria-label="Следующая страница">→</a>';
    }
    echo '</nav>';
}

