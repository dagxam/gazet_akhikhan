<?php
require __DIR__ . '/app/bootstrap.php';
if (!APP_INSTALLED) { header('Location: install.php'); exit; }

$hero = featured_article();
$latest = latest_articles(7, $hero['id'] ?? null);
$cats = categories();
$catBySlug = [];
foreach ($cats as $cat) $catBySlug[$cat['slug']] = $cat;

$demoMini = [
    ['title'=>'В школах района начался новый учебный год','date'=>'23 сентября 2026','views'=>'892','class'=>'school'],
    ['title'=>'Мастера Унцукуля представили свои работы на республиканской выставке','date'=>'22 сентября 2026','views'=>'1 103','class'=>'craft'],
    ['title'=>'В районе прошли спортивные соревнования среди молодежи','date'=>'21 сентября 2026','views'=>'764','class'=>'sport'],
    ['title'=>'Открыт новый ФАП в отдаленном селе','date'=>'20 сентября 2026','views'=>'621','class'=>'house'],
];

$categoryCards = [
    ['slug'=>'obschestvo','name'=>'Общество','class'=>'society'],
    ['slug'=>'ekonomika','name'=>'Экономика','class'=>'economy'],
    ['slug'=>'kultura','name'=>'Культура','class'=>'culture'],
    ['slug'=>'sport','name'=>'Спорт','class'=>'sport'],
    ['slug'=>'lyudi','name'=>'Люди','class'=>'people'],
    ['slug'=>'istoriya','name'=>'История','class'=>'history'],
];

$pageTitle = '';
require __DIR__ . '/partials/header.php';
?>
<a id="top"></a>
<div class="wrap page-shell">

<section class="hero <?=empty($hero['cover_image'])?'hero-reference':''?>"<?php if(!empty($hero['cover_image'])): ?> style="background-image:linear-gradient(90deg,rgba(16,23,25,.82) 0%,rgba(16,23,25,.62) 38%,rgba(16,23,25,.08) 72%),url('<?=e(base_url($hero['cover_image']))?>')"<?php endif; ?>>
  <div class="hero-content">
    <div class="eyebrow"><?=e($hero ? setting('hero_kicker','ГЛАВНАЯ НОВОСТЬ') : 'ГЛАВНАЯ НОВОСТЬ')?></div>
    <h1><?=e($hero['title'] ?? 'Унцукульский район: традиции, развитие и новые возможности')?></h1>
    <p><?=e($hero['excerpt'] ?? 'Наш район сохраняет свои уникальные традиции, движется вперед и открывает новые горизонты для будущих поколений.')?></p>
    <a class="btn" href="<?=e($hero ? article_url($hero) : base_url('admin/article-edit.php'))?>">Читать подробнее <span>→</span></a>
  </div>
  <div class="hero-controls" aria-hidden="true"><b>‹</b><i class="active"></i><i></i><i></i><i></i><b>›</b></div>
</section>

<section class="feature-strip">
  <div>
    <span class="feature-icon feature-mountain" aria-hidden="true"></span>
    <span><strong>Наш район</strong><small>Природа. Люди. Возможности.</small></span>
  </div>
  <div>
    <span class="feature-icon feature-people" aria-hidden="true">●●●</span>
    <span><strong>Наши люди</strong><small>Истории, которые вдохновляют</small></span>
  </div>
  <div>
    <span class="feature-icon feature-rosette" aria-hidden="true">✥</span>
    <span><strong>Наша культура</strong><small>Традиции, ремесла, наследие</small></span>
  </div>
  <div>
    <span class="feature-icon feature-book" aria-hidden="true">▥</span>
    <span><strong>Наша история</strong><small>Память, которая объединяет</small></span>
  </div>
</section>

<div class="news-layout">
<section class="panel news-panel">
  <div class="section-head"><h2>Новости</h2><a href="<?=e(base_url('search.php'))?>">Все новости <span>→</span></a></div>

  <div class="news-grid">
    <?php if($latest): $lead=array_shift($latest); ?>
    <article class="lead-story">
      <a href="<?=e(article_url($lead))?>" class="thumb large-thumb" style="<?=!empty($lead['cover_image']) ? "background-image:url('".e(base_url($lead['cover_image']))."')" : ''?>"></a>
      <span class="tag"><?=e($lead['category_name'] ?: 'Инфраструктура')?></span>
      <h3><a href="<?=e(article_url($lead))?>"><?=e($lead['title'])?></a></h3>
      <p><?=e($lead['excerpt'])?></p>
      <div class="meta"><?=e(ru_date($lead['published_at'] ?: $lead['created_at']))?> <span>◉ <?=number_format((int)$lead['views'],0,'.',' ')?></span></div>
    </article>
    <div class="story-list">
      <?php foreach(array_slice($latest,0,4) as $i=>$item): ?>
      <article>
        <a class="mini-thumb demo-visual demo-<?=e($demoMini[$i]['class'] ?? 'school')?>" href="<?=e(article_url($item))?>" style="<?=!empty($item['cover_image']) ? "background-image:url('".e(base_url($item['cover_image']))."')" : ''?>"></a>
        <div><h4><a href="<?=e(article_url($item))?>"><?=e($item['title'])?></a></h4><div class="meta"><?=e(ru_date($item['published_at'] ?: $item['created_at']))?> <span>◉ <?=number_format((int)$item['views'],0,'.',' ')?></span></div></div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <article class="lead-story">
      <a class="thumb large-thumb demo-road" href="<?=e(base_url('admin/article-edit.php'))?>"></a>
      <span class="tag">Инфраструктура</span>
      <h3><a href="<?=e(base_url('admin/article-edit.php'))?>">В Унцукульском районе продолжается ремонт дорожной сети</a></h3>
      <p>В районе реализуется ряд важных инфраструктурных проектов, направленных на улучшение качества жизни жителей.</p>
      <div class="meta">23 сентября 2026 <span>◉ 1 245</span></div>
    </article>
    <div class="story-list">
      <?php foreach($demoMini as $item): ?>
      <article>
        <a class="mini-thumb demo-visual demo-<?=e($item['class'])?>" href="<?=e(base_url('admin/article-edit.php'))?>"></a>
        <div><h4><a href="<?=e(base_url('admin/article-edit.php'))?>"><?=e($item['title'])?></a></h4><div class="meta"><?=e($item['date'])?> <span>◉ <?=e($item['views'])?></span></div></div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<aside class="sidebar">
  <section class="panel editor-note">
    <div class="section-head"><h2>Слово редактора</h2><button class="dots" aria-label="Дополнительно">•••</button></div>
    <p>«<?=nl2br(e(setting('editor_note','Мы верим, что местная газета — это не просто новости, а мост между людьми, поколениями и родным краем. Наша цель — рассказывать о важном, поддерживать доброе и сохранять наше наследие.')))?>»</p>
    <strong>— Редакция AKHIKHAN.RU</strong>
    <div class="editor-mountains" aria-hidden="true"></div>
  </section>
  <section class="paper-card"><span>Сохраняя традиции<br><b>Создаём будущее</b></span></section>
</aside>
</div>

<section class="panel categories-panel">
  <div class="section-head"><h2>Популярные рубрики</h2><a href="<?=e(base_url('search.php'))?>">Все рубрики <span>→</span></a></div>
  <div class="category-cards">
    <?php foreach($categoryCards as $item):
      $href = isset($catBySlug[$item['slug']]) ? category_url($catBySlug[$item['slug']]) : base_url('search.php?q='.rawurlencode($item['name']));
    ?>
    <a href="<?=e($href)?>">
      <div class="category-art category-<?=e($item['class'])?>"><span class="category-glyph"></span></div>
      <span><?=e($item['name'])?></span><b>→</b>
    </a>
    <?php endforeach; ?>
  </div>
</section>

</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
