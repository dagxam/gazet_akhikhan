<?php
require __DIR__ . '/app/bootstrap.php';
if (!APP_INSTALLED) { header('Location: install.php'); exit; }

$mainNews = latest_main_articles(5);
$hero = $mainNews[0] ?? null;
$latest = latest_articles(12, $hero['id'] ?? null);
$districtNews = latest_articles_by_category_slug('novosti-rayona', 8);
$regionalNews = latest_articles_by_category_slug('regionalnye-novosti', 3);
$sportNews = latest_articles_by_category_slug('sport', 3);
$newspaper = latest_newspaper();
$documents = latest_documents(8);
$rightBlocks = homepage_right_blocks();
$homeGalleryPhotos = latest_gallery_photos(5);
$homeGalleryAlbum = latest_gallery_album();
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
  <article class="hero-story <?=!empty($hero['cover_image'])?'hero-has-cover':'hero-clean'?>" data-interactive-hero<?php if(!empty($hero['cover_image'])):?> style="background-image:linear-gradient(90deg,rgba(19,21,18,.88) 0%,rgba(19,21,18,.57) 45%,rgba(19,21,18,.14) 82%),url('<?=e(base_url($hero['cover_image']))?>')"<?php endif;?>>
    <div class="hero-story-copy">
      <span class="kicker" data-hero-kicker>Главные новости</span>
      <?php if($hero):?>
        <h1 data-hero-title><?=e($hero['title'])?></h1>
        <p data-hero-excerpt><?=e($hero['excerpt'] ?: 'Читайте подробности события в материале «АХИХЪАН».')?></p>
        <a class="story-button" data-hero-link href="<?=e(article_url($hero))?>">Читать материал <span>→</span></a>
      <?php else:?>
        <h1 data-hero-title>Главных новостей пока нет</h1>
        <p data-hero-excerpt>Опубликуйте материал в рубрике «Главные новости», и он появится в этом блоке.</p>
      <?php endif;?>
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

<section class="content-section district-news-section">
  <div class="block-heading news-section-heading">
    <div>
      <span class="heading-kicker">Район</span>
      <h2>Новости района</h2>
    </div>
    <a href="<?=e(isset($catBySlug['novosti-rayona']) ? category_url($catBySlug['novosti-rayona']) : base_url('news.php'))?>">Все новости района →</a>
  </div>

  <div class="news-magazine-grid">
    <?php if($districtNews):
      $lead = $districtNews[0];
      $secondary = array_slice($districtNews,1,4);
    ?>
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
      <article class="news-feature">
        <a class="news-image large" href="<?=e(article_url($lead))?>" style="<?=!empty($lead['cover_image']) ? "background-image:url('".e(base_url($lead['cover_image']))."')" : ''?>"></a>
        <div class="news-feature-body">
          <div class="article-label">Новости района</div>
          <h3><a href="<?=e(article_url($lead))?>"><?=e($lead['title'])?></a></h3>
          <?php if(!empty($lead['excerpt'])):?><p><?=e($lead['excerpt'])?></p><?php endif;?>
          <div class="article-meta"><span><?=e(ru_date($lead['published_at'] ?: $lead['created_at']))?></span><span>◉ <?=number_format((int)$lead['views'],0,'.',' ')?></span></div>
        </div>
      </article>
    <?php else: ?>
      <div class="district-news-empty">
        <span class="heading-kicker">Новости района</span>
        <strong>В этой рубрике пока нет опубликованных материалов</strong>
        <p>Новости появятся здесь автоматически после публикации материала в рубрике «Новости района».</p>
      </div>
    <?php endif; ?>


  </div>
</section>

<section class="content-section regional-sport-sidebar-section">
  <div class="regional-sport-layout">
    <div class="regional-sport-main">

      <section class="dual-news-card regional-news-card">
        <div class="dual-news-head">
          <div>
            <span class="heading-kicker">Регион</span>
            <h2>Региональные новости</h2>
          </div>
          <a href="<?=e(isset($catBySlug['regionalnye-novosti']) ? category_url($catBySlug['regionalnye-novosti']) : base_url('news.php'))?>">Все →</a>
        </div>

        <?php if($regionalNews):?>
          <div class="dual-news-list dual-news-list-three">
            <?php foreach(array_slice($regionalNews,0,3) as $i=>$item):?>
              <article class="dual-news-row dual-news-row-full <?=$i===0?'is-first':''?>">
                <a class="dual-news-thumb" href="<?=e(article_url($item))?>" style="<?=!empty($item['cover_image']) ? "background-image:url('".e(base_url($item['cover_image']))."')" : ''?>"></a>
                <div class="dual-news-row-copy">
                  <span class="article-label">Региональные новости</span>
                  <h4><a href="<?=e(article_url($item))?>"><?=e($item['title'])?></a></h4>
                  <?php if(!empty($item['excerpt'])):?><p><?=e($item['excerpt'])?></p><?php endif;?>
                  <div class="article-meta"><span><?=e(ru_date($item['published_at'] ?: $item['created_at']))?></span><span>◉ <?=number_format((int)$item['views'],0,'.',' ')?></span></div>
                </div>
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

        <?php if($sportNews):?>
          <div class="dual-news-list dual-news-list-three">
            <?php foreach(array_slice($sportNews,0,3) as $i=>$item):?>
              <article class="dual-news-row dual-news-row-full <?=$i===0?'is-first':''?>">
                <a class="dual-news-thumb" href="<?=e(article_url($item))?>" style="<?=!empty($item['cover_image']) ? "background-image:url('".e(base_url($item['cover_image']))."')" : ''?>"></a>
                <div class="dual-news-row-copy">
                  <span class="article-label">Спорт</span>
                  <h4><a href="<?=e(article_url($item))?>"><?=e($item['title'])?></a></h4>
                  <?php if(!empty($item['excerpt'])):?><p><?=e($item['excerpt'])?></p><?php endif;?>
                  <div class="article-meta"><span><?=e(ru_date($item['published_at'] ?: $item['created_at']))?></span><span>◉ <?=number_format((int)$item['views'],0,'.',' ')?></span></div>
                </div>
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

      <section class="home-photo-gallery">
        <div class="home-photo-gallery-head">
          <div>
            <span class="heading-kicker">Фото</span>
            <h2>Фотогалерея</h2>
          </div>
          <a href="<?=e(base_url('gallery.php'))?>">Все фотоальбомы →</a>
        </div>

        <?php if($homeGalleryPhotos):?>
          <div class="home-photo-gallery-grid">
            <?php foreach($homeGalleryPhotos as $i=>$photo):?>
              <a class="home-photo-tile <?=$i===0?'is-featured':''?>" href="<?=e(base_url('gallery.php?album='.$photo['album_id']))?>">
                <img src="<?=e(base_url($photo['image_path']))?>" alt="<?=e($photo['caption'] ?: $photo['album_title'])?>" loading="<?=$i<3?'eager':'lazy'?>">
                <span class="home-photo-tile-overlay"></span>
                <span class="home-photo-tile-copy">
                  <?php if($i===0):?><small><?=e(ru_date($photo['album_date']))?></small><?php endif;?>
                  <strong><?=e($photo['caption'] ?: $photo['album_title'])?></strong>
                  <?php if($i===0):?><b><?=e($photo['album_title'])?> · Открыть альбом →</b><?php endif;?>
                </span>
              </a>
            <?php endforeach;?>
          </div>
          <?php if($homeGalleryAlbum):?>
            <div class="home-photo-gallery-foot">
              <span>Последний альбом</span>
              <a href="<?=e(base_url('gallery.php?album='.$homeGalleryAlbum['id']))?>"><?=e($homeGalleryAlbum['title'])?> <b>→</b></a>
            </div>
          <?php endif;?>
        <?php else:?>
          <div class="home-photo-gallery-empty">
            <span class="home-photo-empty-icon">▣</span>
            <div>
              <strong>Фотографии скоро появятся</strong>
              <p>После публикации фотоальбома в админке последние снимки автоматически появятся здесь.</p>
            </div>
          </div>
        <?php endif;?>
      </section>

    </div>

    <aside class="home-right-sidebar">
      <?php foreach($rightBlocks as $block):
        $blockHref=homepage_right_block_href($block['link_url']??'');
        $blockStyle=in_array($block['style'],['light','accent','dark'],true)?$block['style']:'light';
      ?>
        <section class="right-feature-card right-feature-<?=e($blockStyle)?> <?=!empty($block['image'])?'has-image':''?>"
          <?php if(!empty($block['image'])):?>style="--right-block-image:url('<?=e(base_url($block['image']))?>')"<?php endif;?>>
          <div class="right-feature-overlay"></div>
          <div class="right-feature-content">
            <?php if(!empty($block['kicker'])):?><span class="heading-kicker"><?=e($block['kicker'])?></span><?php endif;?>
            <h3><?=e($block['title'])?></h3>
            <?php if(!empty($block['body'])):?><p><?=nl2br(e($block['body']))?></p><?php endif;?>
            <?php if($blockHref!=='' && !empty($block['link_text'])):?>
              <a href="<?=e($blockHref)?>"><?=e($block['link_text'])?> <b>→</b></a>
            <?php endif;?>
          </div>
        </section>
      <?php endforeach;?>

      <aside class="newspaper-card sidebar-newspaper <?=$newspaper?'has-newspaper':''?>">
        <div class="newspaper-card-top">
          <div>
            <span class="heading-kicker">Газета</span>
            <h3>Свежий выпуск</h3>
          </div>
          <?php if($newspaper):?>
            <span class="newspaper-badge"><?=e($newspaper['issue_number'] ?: 'Новый номер')?></span>
          <?php else:?>
            <span class="newspaper-badge">Архив</span>
          <?php endif;?>
        </div>

        <?php if($newspaper):?>
          <div class="newspaper-live">
            <a class="newspaper-cover-frame" href="<?=e(base_url($newspaper['pdf_file']))?>" target="_blank" aria-label="Открыть PDF газеты">
              <?php if(!empty($newspaper['cover_image'])):?>
                <img src="<?=e(base_url($newspaper['cover_image']))?>" alt="<?=e($newspaper['title'])?>">
              <?php else:?>
                <canvas data-pdf-preview="<?=e(base_url($newspaper['pdf_file']))?>" aria-label="Первая страница газеты"></canvas>
                <span class="newspaper-cover-loading">PDF</span>
              <?php endif;?>
            </a>
            <div class="newspaper-live-copy">
              <span><?=e(ru_date($newspaper['issue_date']))?></span>
              <h4><?=e($newspaper['title'])?></h4>
              <p><?=e($newspaper['issue_number'] ? 'Выпуск '.$newspaper['issue_number'].' доступен для чтения в PDF.' : 'Свежий выпуск газеты доступен для чтения в PDF.')?></p>
              <a class="newspaper-open" href="<?=e(base_url($newspaper['pdf_file']))?>" target="_blank">Читать газету <b>→</b></a>
            </div>
          </div>
        <?php else:?>
          <div class="newspaper-placeholder">
            <div class="newspaper-sheet">
              <span>АХИХЪАН</span>
              <i></i><i></i><i></i>
              <b>Печатный выпуск</b>
            </div>
            <div class="newspaper-copy">
              <h3>Выпусков пока нет</h3>
              <p>После загрузки PDF в разделе «Газета» здесь автоматически появится последний опубликованный номер.</p>
            </div>
          </div>
        <?php endif;?>
      </aside>
    </aside>
  </div>
</section>

<section class="content-section documents-strip-section">
  <div class="block-heading documents-heading">
    <div>
      <span class="heading-kicker">Официальные материалы</span>
      <h2>Последние документы</h2>
    </div>
    <span class="block-note">PDF · Word · Excel · PowerPoint</span>
  </div>

  <?php if($documents):?>
    <div class="documents-strip" aria-label="Последние документы">
      <?php foreach($documents as $doc):
        $formatClass=document_format_class($doc['file_ext']);
        $formatLabel=document_format_label($doc['file_ext']);
      ?>
        <a class="document-card" href="<?=e(base_url($doc['file_path']))?>" target="_blank">
          <span class="document-format-icon <?=$formatClass?>">
            <b><?=e(strtoupper($doc['file_ext']))?></b>
            <small><?=e($formatLabel)?></small>
          </span>
          <span class="document-card-copy">
            <small class="document-date"><?=e(ru_date($doc['document_date']))?></small>
            <strong><?=e($doc['title'])?></strong>
            <?php if(!empty($doc['description'])):?><span><?=e($doc['description'])?></span><?php endif;?>
            <i><?=e(human_file_size((int)$doc['file_size']))?> · Открыть →</i>
          </span>
        </a>
      <?php endforeach;?>
    </div>
  <?php else:?>
    <div class="documents-empty">
      <span class="document-format-icon file"><b>DOC</b><small>ФАЙЛ</small></span>
      <div>
        <strong>Документы пока не опубликованы</strong>
        <p>После добавления в разделе «Документы» они автоматически появятся здесь.</p>
      </div>
    </div>
  <?php endif;?>
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

<?php if($newspaper && empty($newspaper['cover_image'])):?>
<script type="module" id="public-newspaper-pdf-renderer">
const canvases=[...document.querySelectorAll('canvas[data-pdf-preview]')];
if(canvases.length){
  try{
    const pdfjs=await import('https://cdn.jsdelivr.net/npm/pdfjs-dist@4.10.38/build/pdf.min.mjs');
    pdfjs.GlobalWorkerOptions.workerSrc='https://cdn.jsdelivr.net/npm/pdfjs-dist@4.10.38/build/pdf.worker.min.mjs';
    for(const canvas of canvases){
      try{
        const pdf=await pdfjs.getDocument(canvas.dataset.pdfPreview).promise;
        const page=await pdf.getPage(1);
        const base=page.getViewport({scale:1});
        const cssWidth=Math.max(180,canvas.parentElement.clientWidth);
        const ratio=Math.min(window.devicePixelRatio||1,2);
        const viewport=page.getViewport({scale:(cssWidth*ratio)/base.width});
        canvas.width=Math.floor(viewport.width);
        canvas.height=Math.floor(viewport.height);
        await page.render({canvasContext:canvas.getContext('2d'),viewport}).promise;
        canvas.parentElement.querySelector('.newspaper-cover-loading')?.remove();
      }catch(e){}
    }
  }catch(e){}
}
</script>
<?php endif;?>

</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
