<?php
require __DIR__ . '/app/bootstrap.php';
if (!APP_INSTALLED) { header('Location: install.php'); exit; }
$hero = featured_article();
$latest = latest_articles(7, $hero['id'] ?? null);
$cats = categories();
$pageTitle = '';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap page-shell">
<?php if($hero): ?>
<section class="hero" style="<?=!empty($hero['cover_image']) ? "background-image:linear-gradient(90deg,rgba(18,25,25,.82),rgba(18,25,25,.2)),url('".e(base_url($hero['cover_image']))."')" : ''?>">
  <div class="hero-content">
    <div class="eyebrow"><?=e(setting('hero_kicker','ГЛАВНАЯ НОВОСТЬ'))?></div>
    <h1><?=e($hero['title'])?></h1>
    <p><?=e($hero['excerpt'])?></p>
    <a class="btn" href="<?=e(article_url($hero))?>">Читать подробнее →</a>
  </div>
</section>
<?php else: ?>
<section class="hero hero-demo"><div class="hero-content"><div class="eyebrow">ГЛАВНАЯ НОВОСТЬ</div><h1>Унцукульский район: традиции, развитие и новые возможности</h1><p>Современная площадка местной газеты — о людях, событиях, культуре и истории родного края.</p><a class="btn" href="<?=e(base_url('admin/article-edit.php'))?>">Добавить первую новость →</a></div></section>
<?php endif; ?>

<section class="feature-strip">
  <div><b>△</b><span><strong>Наш район</strong><small>Природа. Люди. Возможности.</small></span></div>
  <div><b>◉</b><span><strong>Наши люди</strong><small>Истории, которые вдохновляют</small></span></div>
  <div><b>✥</b><span><strong>Наша культура</strong><small>Традиции, ремесла, наследие</small></span></div>
  <div><b>▤</b><span><strong>Наша история</strong><small>Память, которая объединяет</small></span></div>
</section>

<div class="news-layout">
<section class="panel news-panel">
  <div class="section-head"><h2>Новости</h2><a href="<?=e(base_url('search.php'))?>">Все новости →</a></div>
  <?php if($latest): $lead=array_shift($latest); ?>
  <div class="news-grid">
    <article class="lead-story">
      <a href="<?=e(article_url($lead))?>" class="thumb large-thumb" style="<?=!empty($lead['cover_image']) ? "background-image:url('".e(base_url($lead['cover_image']))."')" : ''?>"></a>
      <span class="tag"><?=e($lead['category_name'] ?: 'Новости')?></span>
      <h3><a href="<?=e(article_url($lead))?>"><?=e($lead['title'])?></a></h3>
      <p><?=e($lead['excerpt'])?></p>
      <div class="meta"><?=e(ru_date($lead['published_at'] ?: $lead['created_at']))?> · ◉ <?=number_format((int)$lead['views'],0,'.',' ')?></div>
    </article>
    <div class="story-list">
      <?php foreach(array_slice($latest,0,4) as $item): ?>
      <article><a class="mini-thumb" href="<?=e(article_url($item))?>" style="<?=!empty($item['cover_image']) ? "background-image:url('".e(base_url($item['cover_image']))."')" : ''?>"></a><div><h4><a href="<?=e(article_url($item))?>"><?=e($item['title'])?></a></h4><div class="meta"><?=e(ru_date($item['published_at'] ?: $item['created_at']))?> · ◉ <?=number_format((int)$item['views'],0,'.',' ')?></div></div></article>
      <?php endforeach; ?>
    </div>
  </div>
  <?php else: ?><div class="empty">Пока нет опубликованных новостей. Добавьте материал в админ-панели.</div><?php endif; ?>
</section>

<aside class="sidebar">
  <section class="panel editor-note"><div class="section-head"><h2>Слово редактора</h2></div><p>«<?=nl2br(e(setting('editor_note')))?>»</p><strong>— Редакция AKHIKHAN.RU</strong></section>
  <section class="paper-card">Сохраняя традиции<br><b>Создаём будущее</b></section>
</aside>
</div>

<section class="panel categories-panel">
<div class="section-head"><h2>Популярные рубрики</h2></div>
<div class="category-cards">
<?php foreach($cats as $cat): ?><a href="<?=e(category_url($cat))?>"><div class="category-art">◇</div><span><?=e($cat['name'])?></span><b>→</b></a><?php endforeach; ?>
</div>
</section>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
