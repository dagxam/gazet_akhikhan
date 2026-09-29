<?php
require __DIR__ . '/app/bootstrap.php';

if (!APP_INSTALLED) {
    http_response_code(503);
    header('Content-Type: application/xml; charset=UTF-8');
    echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';
    exit;
}

header('Content-Type: application/xml; charset=UTF-8');
header('Cache-Control: public, max-age=900');

$urls = [];

function sitemap_xml_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function sitemap_add(array &$urls, string $loc, ?string $lastmod = null, ?string $changefreq = null, ?string $priority = null): void
{
    $loc = trim($loc);
    if ($loc === '') return;

    $entry = ['loc' => $loc];
    if ($lastmod) {
        $ts = strtotime($lastmod);
        if ($ts) $entry['lastmod'] = date('c', $ts);
    }
    if ($changefreq) $entry['changefreq'] = $changefreq;
    if ($priority) $entry['priority'] = $priority;

    $urls[$loc] = $entry;
}

sitemap_add($urls, base_url(), null, 'daily', '1.0');

$publicPages = [
    ['news.php', 'daily', '0.9'],
    ['gallery.php', 'weekly', '0.7'],
    ['videos.php', 'weekly', '0.7'],
    ['about.php', 'monthly', '0.5'],
    ['contacts.php', 'monthly', '0.5'],
];

foreach ($publicPages as [$path, $frequency, $priority]) {
    if (is_file(ROOT_PATH . '/' . $path)) {
        sitemap_add($urls, base_url($path), date('c', filemtime(ROOT_PATH . '/' . $path)), $frequency, $priority);
    }
}

try {
    $categories = db()->query("SELECT name,slug,created_at FROM categories WHERE is_active=1 ORDER BY sort_order,name")->fetchAll();
    foreach ($categories as $category) {
        sitemap_add(
            $urls,
            base_url('category/' . rawurlencode((string)$category['slug'])),
            (string)($category['created_at'] ?? ''),
            'daily',
            '0.8'
        );
    }
} catch (Throwable $e) {
}

try {
    $pages = db()->query("SELECT title,slug,updated_at,created_at FROM static_pages WHERE status='published' ORDER BY id")->fetchAll();
    foreach ($pages as $page) {
        sitemap_add(
            $urls,
            base_url('page/' . rawurlencode((string)$page['slug'])),
            (string)($page['updated_at'] ?: $page['created_at']),
            'monthly',
            '0.6'
        );
    }
} catch (Throwable $e) {
}

try {
    $articles = db()->query("SELECT slug,published_at,created_at,updated_at
        FROM articles
        WHERE status='published'
          AND (published_at IS NULL OR published_at<=CURRENT_TIMESTAMP)
        ORDER BY COALESCE(published_at,created_at) DESC")->fetchAll();

    foreach ($articles as $article) {
        sitemap_add(
            $urls,
            base_url('article/' . rawurlencode((string)$article['slug'])),
            (string)($article['updated_at'] ?: $article['published_at'] ?: $article['created_at']),
            'weekly',
            '0.9'
        );
    }
} catch (Throwable $e) {
}

try {
    $albums = db()->query("SELECT id,updated_at,created_at FROM photo_albums WHERE status='published' ORDER BY album_date DESC,id DESC")->fetchAll();
    foreach ($albums as $album) {
        sitemap_add(
            $urls,
            base_url('gallery.php?album=' . (int)$album['id']),
            (string)($album['updated_at'] ?: $album['created_at']),
            'monthly',
            '0.5'
        );
    }
} catch (Throwable $e) {
}

try {
    $videos = db()->query("SELECT id,updated_at,created_at FROM video_gallery WHERE status='published' ORDER BY video_date DESC,id DESC")->fetchAll();
    foreach ($videos as $video) {
        sitemap_add(
            $urls,
            base_url('videos.php?id=' . (int)$video['id']),
            (string)($video['updated_at'] ?: $video['created_at']),
            'monthly',
            '0.5'
        );
    }
} catch (Throwable $e) {
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $entry) {
    echo "  <url>\n";
    echo '    <loc>' . sitemap_xml_escape($entry['loc']) . "</loc>\n";
    if (!empty($entry['lastmod'])) echo '    <lastmod>' . sitemap_xml_escape($entry['lastmod']) . "</lastmod>\n";
    if (!empty($entry['changefreq'])) echo '    <changefreq>' . sitemap_xml_escape($entry['changefreq']) . "</changefreq>\n";
    if (!empty($entry['priority'])) echo '    <priority>' . sitemap_xml_escape($entry['priority']) . "</priority>\n";
    echo "  </url>\n";
}
echo "</urlset>\n";
