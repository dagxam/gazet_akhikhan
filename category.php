<?php
require __DIR__ . '/app/bootstrap.php';
if (!APP_INSTALLED) { header('Location: install.php'); exit; }
$slug = trim($_GET['slug'] ?? '');
$q = db()->prepare('SELECT * FROM categories WHERE slug=? AND is_active=1 LIMIT 1');
$q->execute([$slug]);
$category=$q->fetch();
if(!$category){ http_response_code(404); exit('Рубрика не найдена'); }

$q=db()->prepare("SELECT a.*,c.name category_name,c.slug category_slug
FROM articles a LEFT JOIN categories c ON c.id=a.category_id
WHERE a.category_id=? AND a.status='published'
ORDER BY COALESCE(a.published_at,a.created_at) DESC");
$q->execute([$category['id']]);
$articles=$q->fetchAll();

$pageTitle=$category['name'];
require __DIR__.'/partials/header.php';
?>
<div class="wrap page-shell"><section class="panel listing"><div class="section-head"><h1><?=e($category['name'])?></h1></div>
<?php foreach($articles as $a): ?><article class="list-card"><a class="list-thumb" href="<?=e(article_url($a))?>" style="<?=!empty($a['cover_image']) ? "background-image:url('".e(base_url($a['cover_image']))."')" : ''?>"></a><div><div class="meta"><?=e(ru_date($a['published_at'] ?: $a['created_at']))?></div><h2><a href="<?=e(article_url($a))?>"><?=e($a['title'])?></a></h2><p><?=e($a['excerpt'])?></p></div></article><?php endforeach; ?>
<?php if(!$articles): ?><div class="empty">В этой рубрике пока нет публикаций.</div><?php endif; ?>
</section></div>
<?php require __DIR__.'/partials/footer.php'; ?>
