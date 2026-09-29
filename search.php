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
$pageDescription='Поиск по публикациям сетевого издания «АХИХЪАН».';
$seoCanonical=base_url('search.php');
$seoRobots='noindex,follow,noarchive';
require __DIR__.'/partials/header.php';
?>
<div class="wrap page-shell">
  <div class="page-head">
    <div>
      <span class="heading-kicker">Архив издания</span>
      <h1>Поиск</h1>
      <p><?= $term!=='' ? 'Результаты по запросу «'.e($term).'»' : 'Найдите публикации по заголовку, анонсу или тексту материала.' ?></p>
    </div>
    <div class="page-head-mark" aria-hidden="true"></div>
  </div>

  <form class="big-search" method="get">
    <input name="q" value="<?=e($term)?>" placeholder="Например: культура, школа, спорт">
    <button>Найти</button>
  </form>

  <?php if($articles): ?>
    <section class="listing-grid">
      <?php foreach($articles as $a): ?>
        <article class="list-card">
          <a class="list-thumb<?=empty($a['cover_image'])?' demo-road':''?>" href="<?=e(article_url($a))?>" style="<?=!empty($a['cover_image']) ? "background-image:url('".e(base_url($a['cover_image']))."')" : ''?>"></a>
          <div class="list-card-body">
            <div class="article-label"><?=e($a['category_name'] ?: 'Новости')?></div>
            <h2><a href="<?=e(article_url($a))?>"><?=e($a['title'])?></a></h2>
            <p><?=e($a['excerpt'])?></p>
            <div class="article-meta"><span><?=e(ru_date($a['published_at'] ?: $a['created_at']))?></span><span>◉ <?=number_format((int)$a['views'],0,'.',' ')?></span></div>
          </div>
        </article>
      <?php endforeach; ?>
    </section>
  <?php elseif($term!==''): ?>
    <div class="empty">По вашему запросу ничего не найдено. Попробуйте изменить формулировку.</div>
  <?php else: ?>
    <div class="empty">Введите поисковый запрос выше.</div>
  <?php endif; ?>
</div>
<?php require __DIR__.'/partials/footer.php'; ?>
