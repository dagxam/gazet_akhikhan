<?php
require __DIR__ . '/app/bootstrap.php';
if (!APP_INSTALLED) { header('Location: install.php'); exit; }
$term = trim($_GET['q'] ?? '');
$articles = [];
if($term!==''){
  $q=db()->prepare("SELECT a.*,c.name category_name,c.slug category_slug
  FROM articles a LEFT JOIN categories c ON c.id=a.category_id
  WHERE a.status='published' AND (a.title LIKE ? OR a.excerpt LIKE ? OR a.content LIKE ?)
  ORDER BY COALESCE(a.published_at,a.created_at) DESC LIMIT 50");
  $like='%'.$term.'%';
  $q->execute([$like,$like,$like]);
  $articles=$q->fetchAll();
}
$pageTitle='Поиск';
require __DIR__.'/partials/header.php';
?>
<div class="wrap page-shell"><section class="panel listing"><div class="section-head"><h1>Поиск</h1></div>
<form class="big-search"><input name="q" value="<?=e($term)?>" placeholder="Введите запрос"><button>Найти</button></form>
<?php foreach($articles as $a): ?><article class="list-card"><a class="list-thumb" href="<?=e(article_url($a))?>" style="<?=!empty($a['cover_image']) ? "background-image:url('".e(base_url($a['cover_image']))."')" : ''?>"></a><div><div class="meta"><?=e(ru_date($a['published_at'] ?: $a['created_at']))?></div><h2><a href="<?=e(article_url($a))?>"><?=e($a['title'])?></a></h2><p><?=e($a['excerpt'])?></p></div></article><?php endforeach; ?>
<?php if($term!=='' && !$articles): ?><div class="empty">По вашему запросу ничего не найдено.</div><?php endif; ?>
</section></div>
<?php require __DIR__.'/partials/footer.php'; ?>
