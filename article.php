<?php
require __DIR__ . '/app/bootstrap.php';
if (!APP_INSTALLED) { header('Location: install.php'); exit; }
$slug = trim($_GET['slug'] ?? '');
$q = db()->prepare("SELECT a.*,c.name category_name,c.slug category_slug,u.name author_name
                    FROM articles a LEFT JOIN categories c ON c.id=a.category_id LEFT JOIN users u ON u.id=a.author_id
                    WHERE a.slug=? AND a.status='published' LIMIT 1");
$q->execute([$slug]);
$article = $q->fetch();
if(!$article){ http_response_code(404); $pageTitle='Материал не найден'; require __DIR__.'/partials/header.php'; echo '<div class="wrap page-shell"><div class="panel article-body"><h1>Материал не найден</h1></div></div>'; require __DIR__.'/partials/footer.php'; exit; }
db()->prepare('UPDATE articles SET views=views+1 WHERE id=?')->execute([$article['id']]);
$pageTitle = $article['title'];
$pageDescription = $article['excerpt'];
require __DIR__ . '/partials/header.php';
?>
<div class="wrap page-shell article-page">
<article class="panel article-body">
<div class="article-kicker"><?=e($article['category_name'] ?: 'Новости')?></div>
<h1><?=e($article['title'])?></h1>
<div class="meta"><?=e(ru_date($article['published_at'] ?: $article['created_at']))?> · <?=e($article['author_name'] ?: 'Редакция')?> · ◉ <?=number_format((int)$article['views']+1,0,'.',' ')?></div>
<?php if($article['cover_image']): ?><img class="article-cover" src="<?=e(base_url($article['cover_image']))?>" alt=""><?php endif; ?>
<?php if($article['excerpt']): ?><p class="article-lead"><?=e($article['excerpt'])?></p><?php endif; ?>
<div class="article-content"><?=nl2br(e($article['content']))?></div>
</article>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
