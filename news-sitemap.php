<?php
require __DIR__ . '/app/bootstrap.php';

if (!APP_INSTALLED) {
    http_response_code(503);
    header('Content-Type: application/xml; charset=UTF-8');
    echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"></urlset>';
    exit;
}

header('Content-Type: application/xml; charset=UTF-8');
header('Cache-Control: public, max-age=300');

function news_sitemap_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

$articles = [];
try {
    $rows = db()->query("SELECT title,slug,published_at,created_at
        FROM articles
        WHERE status='published'
          AND (published_at IS NULL OR published_at<=CURRENT_TIMESTAMP)
        ORDER BY COALESCE(published_at,created_at) DESC
        LIMIT 1000")->fetchAll();

    $cutoff = time() - 2 * 86400;
    foreach ($rows as $row) {
        $published = (string)($row['published_at'] ?: $row['created_at']);
        $ts = strtotime($published);
        if (!$ts || $ts < $cutoff) continue;
        $row['_published_ts'] = $ts;
        $articles[] = $row;
    }
} catch (Throwable $e) {
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n";
foreach ($articles as $article) {
    echo "  <url>\n";
    echo '    <loc>' . news_sitemap_escape(base_url('article/' . rawurlencode((string)$article['slug']))) . "</loc>\n";
    echo "    <news:news>\n";
    echo "      <news:publication>\n";
    echo "        <news:name>АХИХЪАН</news:name>\n";
    echo "        <news:language>ru</news:language>\n";
    echo "      </news:publication>\n";
    echo '      <news:publication_date>' . news_sitemap_escape(date('c', (int)$article['_published_ts'])) . "</news:publication_date>\n";
    echo '      <news:title>' . news_sitemap_escape((string)$article['title']) . "</news:title>\n";
    echo "    </news:news>\n";
    echo "  </url>\n";
}
echo "</urlset>\n";
