<?php
require __DIR__ . '/app/bootstrap.php';
if (!APP_INSTALLED) { header('Location: install.php'); exit; }

$hero = featured_article();
$latest = latest_articles(12, $hero['id'] ?? null);
$mainNews = latest_main_articles(5, $hero['id'] ?? null);
$districtNews = latest_articles_by_category_slug('novosti-rayona', 8);
$regionalNews = latest_articles_by_category_slug('regionalnye-novosti', 3);
$sportNews = latest_articles_by_category_slug('sport', 3);
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
        <span class="heading-kicker">Главное</span>
        <h2>Главные новости</h2>
      </div>
      <a href="<?=e(base_url('news.php'))?>">Все →</a>
    </div>
    <div class="latest-list">
      <?php if($mainNews): ?>
        <?php foreach($mainNews as $i=>$item): ?>
          <article class="latest-item<?=$i===0?' is-active':''?>"
                   tabindex="0"
                   data-hero-title="<?=e($item['title'])?>"
                   data-hero-excerpt="<?=e($item['excerpt'] ?: 'Читайте подробности события в материале «АХИХЪАН».')?>"
                   data-hero-kicker="Главные новости"
                   data-hero-url="<?=e(article_url($item))?>"
                   data-hero-cover="<?=e(!empty($item['cover_image']) ? base_url($item['cover_image']) : '')?>">
            <time><?=e(ru_date($item['published_at'] ?: $item['created_at']))?></time>
            <h3><a href="<?=e(article_url($item))?>"><?=e($item['title'])?></a></h3>
            <div class="tiny-meta">Главные новости · ◉ <?=number_format((int)$item['views'],0,'.',' ')?></div>
          </article>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="latest-empty">
          <strong>Главных новостей пока нет</strong>
          <span>При добавлении новости выберите рубрику «Главные новости» — материал появится здесь.</span>
        </div>
      <?php endif; ?>
    </div>
  </aside>
</section>

<section class="district-ribbon">
  <div class="district-ribbon-title">
    <span>АХИХЪАН</span>
    <strong>Район в фокусе</strong>
  </div>
  <a href="<?=e(nav_link_for_slug('novosti-rayona','Новости района'))?>"><b>01</b><span>Новости района<small>Главное рядом</small></span></a>
  <a href="<?=e(nav_link_for_slug('lyudi','Люди'))?>"><b>02</b><span>Наши люди<small>Истории земляков</small></span></a>
  <a href="<?=e(nav_link_for_slug('kultura','Культура'))?>"><b>03</b><span>Культура<small>Унцукульские традиции</small></span></a>
  <a href="<?=e(nav_link_for_slug('istoriya','История'))?>"><b>04</b><span>История<small>Память и места</small></span></a>
</section>

<section class="content-section">
  <div class="block-heading">
    <div>
      <span class="heading-kicker">Новости района</span>
      <h2>Последние новости района</h2>
    </div>
    <a href="<?=e(isset($catBySlug['novosti-rayona']) ? category_url($catBySlug['novosti-rayona']) : base_url('news.php'))?>">Все новости района →</a>
  </div>

  <div class="news-magazine-grid">
    <?php if($districtNews):
      $lead = $districtNews[0];
      $secondary = array_slice($districtNews,1,4);
    ?>
      <article class="news-feature">
        <a class="news-image large" href="<?=e(article_url($lead))?>" style="<?=!empty($lead['cover_image']) ? "background-image:url('".e(base_url($lead['cover_image']))."')" : ''?>"></a>
        <div class="news-feature-body">
          <div class="article-label">Новости района</div>
          <h3><a href="<?=e(article_url($lead))?>"><?=e($lead['title'])?></a></h3>
          <?php if(!empty($lead['excerpt'])):?><p><?=e($lead['excerpt'])?></p><?php endif;?>
          <div class="article-meta"><span><?=e(ru_date($lead['published_at'] ?: $lead['created_at']))?></span><span>◉ <?=number_format((int)$lead['views'],0,'.',' ')?></span></div>
        </div>
      </article>
      <div class="news-stack">
        <?php foreach($secondary as $item): ?>
          <article class="news-row">
            <a class="news-image small" href="<?=e(article_url($item))?>" style="<?=!empty($item['cover_image']) ? "background-image:url('".e(base_url($item['cover_image']))."')" : ''?>"></a>
            <div>
              <div class="article-label">Новости района</div>
              <h3><a href="<?=e(article_url($item))?>"><?=e($item['title'])?></a></h3>
              <div class="article-meta"><span><?=e(ru_date($item['published_at'] ?: $item['created_at']))?></span><span>◉ <?=number_format((int)$item['views'],0,'.',' ')?></span></div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="district-news-empty">
        <span class="heading-kicker">Новости района</span>
        <strong>В этой рубрике пока нет опубликованных материалов</strong>
        <p>Новости появятся здесь автоматически после публикации материала в рубрике «Новости района».</p>
      </div>
    <?php endif; ?>

    <div class="magazine-side-column">
      <aside class="editorial-card editorial-card-compact">
        <div class="editorial-frame"></div>
        <div class="editorial-copy">
          <span class="heading-kicker">От редакции</span>
          <h3>О районе — с уважением к людям и истории</h3>
          <p>«<?=nl2br(e(setting('editor_note','Наша задача — рассказывать о важном для жителей района, сохранять память о прошлом и показывать людей, которые сегодня меняют родной край.')))?>»</p>
          <strong>Редакция «АХИХЪАН»</strong>
        </div>
      </aside>

      <aside class="newspaper-card">
        <div class="newspaper-card-top">
          <span class="heading-kicker">Газета</span>
          <span class="newspaper-badge">Скоро</span>
        </div>
        <div class="newspaper-placeholder">
          <div class="newspaper-sheet">
            <span>АХИХЪАН</span>
            <i></i><i></i><i></i>
            <b>Печатный выпуск</b>
          </div>
          <div class="newspaper-copy">
            <h3>Свежий номер газеты</h3>
            <p>Здесь будет размещаться обложка последнего выпуска, номер и ссылка для чтения.</p>
          </div>
        </div>
      </aside>
    </div>
  </div>
</section>

<section class="content-section dual-news-section">
  <div class="dual-news-grid">

    <section class="dual-news-card regional-news-card">
      <div class="dual-news-head">
        <div>
          <span class="heading-kicker">Регион</span>
          <h2>Региональные новости</h2>
        </div>
        <a href="<?=e(isset($catBySlug['regionalnye-novosti']) ? category_url($catBySlug['regionalnye-novosti']) : base_url('news.php'))?>">Все →</a>
      </div>

      <?php if($regionalNews):
        $regionalLead = $regionalNews[0];
        $regionalMore = array_slice($regionalNews,1,2);
      ?>
        <article class="dual-news-lead">
          <a class="dual-news-image" href="<?=e(article_url($regionalLead))?>" style="<?=!empty($regionalLead['cover_image']) ? "background-image:url('".e(base_url($regionalLead['cover_image']))."')" : ''?>"></a>
          <div class="dual-news-lead-copy">
            <span class="article-label">Региональные новости</span>
            <h3><a href="<?=e(article_url($regionalLead))?>"><?=e($regionalLead['title'])?></a></h3>
            <?php if(!empty($regionalLead['excerpt'])):?><p><?=e($regionalLead['excerpt'])?></p><?php endif;?>
            <div class="article-meta"><span><?=e(ru_date($regionalLead['published_at'] ?: $regionalLead['created_at']))?></span><span>◉ <?=number_format((int)$regionalLead['views'],0,'.',' ')?></span></div>
          </div>
        </article>

        <div class="dual-news-list">
          <?php foreach($regionalMore as $item):?>
            <article class="dual-news-row">
              <div>
                <span class="article-label">Регион</span>
                <h4><a href="<?=e(article_url($item))?>"><?=e($item['title'])?></a></h4>
                <div class="article-meta"><span><?=e(ru_date($item['published_at'] ?: $item['created_at']))?></span><span>◉ <?=number_format((int)$item['views'],0,'.',' ')?></span></div>
              </div>
              <a class="dual-news-thumb" href="<?=e(article_url($item))?>" style="<?=!empty($item['cover_image']) ? "background-image:url('".e(base_url($item['cover_image']))."')" : ''?>"></a>
            </article>
          <?php endforeach;?>
        </div>
      <?php else:?>
        <div class="dual-news-empty">
          <strong>Региональных новостей пока нет</strong>
          <p>После публикации материалов в рубрике «Региональные новости» они автоматически появятся здесь.</p>
        </div>
      <?php endif;?>
    </section>

    <section class="dual-news-card sport-news-card">
      <div class="dual-news-head">
        <div>
          <span class="heading-kicker">Спорт</span>
          <h2>Спортивные новости</h2>
        </div>
        <a href="<?=e(isset($catBySlug['sport']) ? category_url($catBySlug['sport']) : base_url('news.php'))?>">Все →</a>
      </div>

      <?php if($sportNews):
        $sportLead = $sportNews[0];
        $sportMore = array_slice($sportNews,1,2);
      ?>
        <article class="dual-news-lead">
          <a class="dual-news-image" href="<?=e(article_url($sportLead))?>" style="<?=!empty($sportLead['cover_image']) ? "background-image:url('".e(base_url($sportLead['cover_image']))."')" : ''?>"></a>
          <div class="dual-news-lead-copy">
            <span class="article-label">Спорт</span>
            <h3><a href="<?=e(article_url($sportLead))?>"><?=e($sportLead['title'])?></a></h3>
            <?php if(!empty($sportLead['excerpt'])):?><p><?=e($sportLead['excerpt'])?></p><?php endif;?>
            <div class="article-meta"><span><?=e(ru_date($sportLead['published_at'] ?: $sportLead['created_at']))?></span><span>◉ <?=number_format((int)$sportLead['views'],0,'.',' ')?></span></div>
          </div>
        </article>

        <div class="dual-news-list">
          <?php foreach($sportMore as $item):?>
            <article class="dual-news-row">
              <div>
                <span class="article-label">Спорт</span>
                <h4><a href="<?=e(article_url($item))?>"><?=e($item['title'])?></a></h4>
                <div class="article-meta"><span><?=e(ru_date($item['published_at'] ?: $item['created_at']))?></span><span>◉ <?=number_format((int)$item['views'],0,'.',' ')?></span></div>
              </div>
              <a class="dual-news-thumb" href="<?=e(article_url($item))?>" style="<?=!empty($item['cover_image']) ? "background-image:url('".e(base_url($item['cover_image']))."')" : ''?>"></a>
            </article>
          <?php endforeach;?>
        </div>
      <?php else:?>
        <div class="dual-news-empty">
          <strong>Спортивных новостей пока нет</strong>
          <p>После публикации материалов в рубрике «Спорт» они автоматически появятся здесь.</p>
        </div>
      <?php endif;?>
    </section>

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
