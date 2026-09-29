<?php
require __DIR__ . '/app/bootstrap.php';
if (!APP_INSTALLED) { header('Location: install.php'); exit; }

$q=db()->query("SELECT a.*,c.name category_name,c.slug category_slug
FROM articles a LEFT JOIN categories c ON c.id=a.category_id
WHERE a.status='published'
ORDER BY COALESCE(a.published_at,a.created_at) DESC LIMIT 100");
$articles=$q->fetchAll();

$pageTitle='Новости';
$pageDescription='Последние публикации сетевого издания «АХИХЪАН» об Унцукульском районе.';
require __DIR__.'/partials/header.php';
?>
<div class="wrap page-shell">
  <div class="page-head">
    <div>
      <span class="heading-kicker">АХИХЪАН</span>
      <h1>Новости района</h1>
      <p>События, люди и темы, важные для Унцукульского района Республики Дагестан.</p>
    </div>
    <div class="page-head-mark" aria-hidden="true"></div>
  </div>

  <?php if($articles): ?>
  <section class="listing-grid">
    <?php foreach($articles as $a): ?>
      <article class="list-card">
        <a class="list-thumb<?=empty($a['cover_image'])?' demo-road':''?>" href="<?=e(article_url($a))?>" style="<?=!empty($a['cover_image']) ? "background-image:url('".e(base_url($a['cover_image']))."')" : ''?>"></a>
        <div class="list-card-body">
          <div class="article-label"><?=e($a['category_name'] ?: 'Новости')?></div>
          <h2><a href="<?=e(article_url($a))?>"><?=e($a['title'])?></a></h2>
          <p><?=e(rich_text_excerpt($a['excerpt'],220))?></p>
          <div class="article-meta"><span><?=e(ru_date($a['published_at'] ?: $a['created_at']))?></span><span>◉ <?=number_format((int)$a['views'],0,'.',' ')?></span></div>
        </div>
      </article>
    <?php endforeach; ?>
  </section>
  <?php else: ?>
    <div class="empty">Пока нет опубликованных новостей. После публикации материалов они появятся здесь автоматически.</div>
  <?php endif; ?>
</div>
<?php require __DIR__.'/partials/footer.php'; ?>
