<?php
require __DIR__ . '/app/bootstrap.php';
if (!APP_INSTALLED) { header('Location: install.php'); exit; }

$hero = featured_article();
$latest = latest_articles(12, $hero['id'] ?? null);
$cats = categories();
$catBySlug = [];
foreach ($cats as $cat) $catBySlug[$cat['slug']] = $cat;

$demoNews = [
  ['title'=>'В Унцукульском районе продолжается обновление дорожной инфраструктуры','excerpt'=>'Работы направлены на повышение безопасности и доступности населённых пунктов района.','date'=>'28 сентября 2026','views'=>'1 245','class'=>'road'],
  ['title'=>'В школах района проходит новый учебный год','excerpt'=>'Ученики и педагоги начали новый учебный сезон.','date'=>'27 сентября 2026','views'=>'892','class'=>'school'],
  ['title'=>'Мастера Унцукуля представили традиционные изделия','excerpt'=>'Народные художественные промыслы остаются одной из визитных карточек района.','date'=>'26 сентября 2026','views'=>'1 103','class'=>'craft'],
  ['title'=>'В районе прошли спортивные соревнования среди молодёжи','excerpt'=>'Команды из разных населённых пунктов встретились на районной площадке.','date'=>'25 сентября 2026','views'=>'764','class'=>'sport'],
  ['title'=>'Истории земляков: люди, которые сохраняют связь поколений','excerpt'=>'Рассказываем о жителях района, их труде и семейных традициях.','date'=>'24 сентября 2026','views'=>'621','class'=>'people'],
];

$categoryCards = [
  ['slug'=>'obschestvo','name'=>'Общество','subtitle'=>'Жизнь района','class'=>'society'],
  ['slug'=>'ekonomika','name'=>'Экономика','subtitle'=>'Развитие и проекты','class'=>'economy'],
  ['slug'=>'kultura','name'=>'Культура','subtitle'=>'Наследие и ремёсла','class'=>'culture'],
  ['slug'=>'sport','name'=>'Спорт','subtitle'=>'События и команды','class'=>'sport'],
  ['slug'=>'lyudi','name'=>'Люди','subtitle'=>'Истории земляков','class'=>'people'],
  ['slug'=>'istoriya','name'=>'История','subtitle'=>'Память и места','class'=>'history'],
];

$pageTitle = '';
$pageDescription = 'АХИХЪАН — сетевое издание Унцукульского района Республики Дагестан.';
require __DIR__ . '/partials/header.php';
?>

<div class="wrap home-shell">

<section class="home-lead-grid">
  <article class="hero-story <?=empty($hero['cover_image'])?'hero-reference':''?>" data-interactive-hero><?php if(!empty($hero['cover_image'])): ?> style="background-image:linear-gradient(90deg,rgba(19,21,18,.86) 0%,rgba(19,21,18,.54) 44%,rgba(19,21,18,.08) 78%),url('<?=e(base_url($hero['cover_image']))?>')"<?php endif; ?>>
    <div class="hero-story-copy">
      <span class="kicker" data-hero-kicker><?=e($hero ? setting('hero_kicker','Главная тема') : 'Главная тема')?></span>
      <h1 data-hero-title><?=e($hero['title'] ?? 'Унцукульский район: традиции, люди и движение вперёд')?></h1>
      <p data-hero-excerpt><?=e($hero['excerpt'] ?? 'АХИХЪАН рассказывает о событиях района, людях, которые его создают, и наследии, которое объединяет поколения.')?></p>
      <a class="story-button" data-hero-link href="<?=e($hero ? article_url($hero) : base_url('news.php'))?>">Читать материал <span>→</span></a>
    </div>
    <div class="hero-location">
      <span>Унцукульский район</span>
      <b>Дагестан</b>
    </div>
  </article>

  <aside class="latest-panel">
    <div class="block-heading compact">
      <div>
        <span class="heading-kicker">Лента</span>
        <h2>Последние новости</h2>
      </div>
      <a href="<?=e(base_url('news.php'))?>">Все →</a>
    </div>
    <div class="latest-list">
      <?php if($latest): ?>
        <?php foreach(array_slice($latest,0,5) as $i=>$item): ?>
          <article class="latest-item<?=$i===0?' is-active':''?>"
                   tabindex="0"
                   data-hero-title="<?=e($item['title'])?>"
                   data-hero-excerpt="<?=e($item['excerpt'] ?: 'Читайте подробности события в материале «АХИХЪАН».')?>"
                   data-hero-kicker="<?=e($item['category_name'] ?: 'Новости района')?>"
                   data-hero-url="<?=e(article_url($item))?>"
                   data-hero-cover="<?=e(!empty($item['cover_image']) ? base_url($item['cover_image']) : '')?>">
            <time><?=e(ru_date($item['published_at'] ?: $item['created_at']))?></time>
            <h3><a href="<?=e(article_url($item))?>"><?=e($item['title'])?></a></h3>
            <div class="tiny-meta"><?=e($item['category_name'] ?: 'Новости')?> · ◉ <?=number_format((int)$item['views'],0,'.',' ')?></div>
          </article>
        <?php endforeach; ?>
      <?php else: ?>
        <?php foreach($demoNews as $i=>$item): ?>
          <article class="latest-item<?=$i===0?' is-active':''?>"
                   tabindex="0"
                   data-hero-title="<?=e($item['title'])?>"
                   data-hero-excerpt="<?=e($item['excerpt'])?>"
                   data-hero-kicker="Новости района"
                   data-hero-url="<?=e(base_url('news.php'))?>"
                   data-hero-cover="">
            <time><?=e($item['date'])?></time>
            <h3><a href="<?=e(base_url('news.php'))?>"><?=e($item['title'])?></a></h3>
            <div class="tiny-meta">Новости района · ◉ <?=e($item['views'])?></div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </aside>
</section>

<section class="district-ribbon">
  <div class="district-ribbon-title">
    <span>АХИХЪАН</span>
    <strong>Район в фокусе</strong>
  </div>
  <a href="<?=e(nav_link_for_slug('obschestvo','Общество'))?>"><b>01</b><span>События района<small>Главное рядом</small></span></a>
  <a href="<?=e(nav_link_for_slug('lyudi','Люди'))?>"><b>02</b><span>Наши люди<small>Истории земляков</small></span></a>
  <a href="<?=e(nav_link_for_slug('kultura','Культура'))?>"><b>03</b><span>Культура<small>Унцукульские традиции</small></span></a>
  <a href="<?=e(nav_link_for_slug('istoriya','История'))?>"><b>04</b><span>История<small>Память и места</small></span></a>
</section>

<section class="content-section">
  <div class="block-heading">
    <div>
      <span class="heading-kicker">Главное</span>
      <h2>Новости района</h2>
    </div>
    <a href="<?=e(base_url('news.php'))?>">Смотреть все материалы →</a>
  </div>

  <div class="news-magazine-grid">
    <?php if($latest): 
      $lead = $latest[5] ?? $latest[0];
      $secondary = array_slice($latest,6,4);
      if(!$secondary) $secondary = array_slice($latest,1,4);
    ?>
      <article class="news-feature">
        <a class="news-image large" href="<?=e(article_url($lead))?>" style="<?=!empty($lead['cover_image']) ? "background-image:url('".e(base_url($lead['cover_image']))."')" : ''?>"></a>
        <div class="news-feature-body">
          <div class="article-label"><?=e($lead['category_name'] ?: 'Новости')?></div>
          <h3><a href="<?=e(article_url($lead))?>"><?=e($lead['title'])?></a></h3>
          <p><?=e($lead['excerpt'])?></p>
          <div class="article-meta"><span><?=e(ru_date($lead['published_at'] ?: $lead['created_at']))?></span><span>◉ <?=number_format((int)$lead['views'],0,'.',' ')?></span></div>
        </div>
      </article>
      <div class="news-stack">
        <?php foreach($secondary as $i=>$item): ?>
          <article class="news-row">
            <a class="news-image small demo-<?=e($demoNews[$i]['class'] ?? 'road')?>" href="<?=e(article_url($item))?>" style="<?=!empty($item['cover_image']) ? "background-image:url('".e(base_url($item['cover_image']))."')" : ''?>"></a>
            <div>
              <div class="article-label"><?=e($item['category_name'] ?: 'Новости')?></div>
              <h3><a href="<?=e(article_url($item))?>"><?=e($item['title'])?></a></h3>
              <div class="article-meta"><span><?=e(ru_date($item['published_at'] ?: $item['created_at']))?></span><span>◉ <?=number_format((int)$item['views'],0,'.',' ')?></span></div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <article class="news-feature">
        <a class="news-image large demo-road" href="<?=e(base_url('news.php'))?>"></a>
        <div class="news-feature-body">
          <div class="article-label">Инфраструктура</div>
          <h3><a href="<?=e(base_url('news.php'))?>"><?=e($demoNews[0]['title'])?></a></h3>
          <p><?=e($demoNews[0]['excerpt'])?></p>
          <div class="article-meta"><span><?=e($demoNews[0]['date'])?></span><span>◉ <?=e($demoNews[0]['views'])?></span></div>
        </div>
      </article>
      <div class="news-stack">
        <?php foreach(array_slice($demoNews,1,4) as $item): ?>
          <article class="news-row">
            <a class="news-image small demo-<?=e($item['class'])?>" href="<?=e(base_url('news.php'))?>"></a>
            <div>
              <div class="article-label">Новости района</div>
              <h3><a href="<?=e(base_url('news.php'))?>"><?=e($item['title'])?></a></h3>
              <div class="article-meta"><span><?=e($item['date'])?></span><span>◉ <?=e($item['views'])?></span></div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <aside class="editorial-card">
      <div class="editorial-frame"></div>
      <div class="editorial-copy">
        <span class="heading-kicker">От редакции</span>
        <h3>О районе — с уважением к людям и истории</h3>
        <p>«<?=nl2br(e(setting('editor_note','Наша задача — рассказывать о важном для жителей района, сохранять память о прошлом и показывать людей, которые сегодня меняют родной край.')))?>»</p>
        <strong>Редакция «АХИХЪАН»</strong>
      </div>
    </aside>
  </div>
</section>

<section class="content-section categories-section">
  <div class="block-heading">
    <div>
      <span class="heading-kicker">Навигация</span>
      <h2>Рубрики издания</h2>
    </div>
    <span class="block-note">Унцукульский район · Республика Дагестан</span>
  </div>
  <div class="section-cards">
    <?php foreach($categoryCards as $item):
      $href = isset($catBySlug[$item['slug']]) ? category_url($catBySlug[$item['slug']]) : base_url('search.php?q='.rawurlencode($item['name']));
    ?>
      <a class="section-card section-<?=e($item['class'])?>" href="<?=e($href)?>">
        <span class="section-number">0<?=array_search($item,$categoryCards,true)+1?></span>
        <div class="section-card-image"></div>
        <div class="section-card-body">
          <strong><?=e($item['name'])?></strong>
          <small><?=e($item['subtitle'])?></small>
          <b>Открыть рубрику →</b>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="heritage-feature">
  <div class="heritage-visual"></div>
  <div class="heritage-feature-copy">
    <span class="heading-kicker">Унцукульский район</span>
    <h2>Традиции, которые остаются живыми</h2>
    <p>Горный Дагестан, ремёсла, семейная память, история селений и судьбы людей — важная часть редакционной повестки «АХИХЪАН».</p>
    <div class="heritage-links">
      <a href="<?=e(nav_link_for_slug('kultura','Культура'))?>">Культура →</a>
      <a href="<?=e(nav_link_for_slug('istoriya','История'))?>">История →</a>
      <a href="<?=e(nav_link_for_slug('lyudi','Люди'))?>">Люди →</a>
    </div>
  </div>
</section>

</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
