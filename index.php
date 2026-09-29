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
$homeVideos = array_slice(video_gallery_items(true),0,6);
$cats = categories();
$catBySlug = [];
foreach ($cats as $cat) $catBySlug[$cat['slug']] = $cat;

$heroLocationCity = $hero && trim((string)($hero['location_city']??''))!=='' ? trim((string)$hero['location_city']) : 'Унцукульский район';
$heroLocationRegion = $hero && trim((string)($hero['location_region']??''))!=='' ? trim((string)$hero['location_region']) : 'Дагестан';

$demoNews = [
  ['title'=>'В Унцукульском районе продолжается обновление дорожной инфраструктуры','excerpt'=>'Работы направлены на повышение безопасности и доступности населённых пунктов района.','date'=>'28 сентября 2026','views'=>'1 245','class'=>'road'],
  ['title'=>'В школах района проходит новый учебный год','excerpt'=>'Ученики и педагоги начали новый учебный сезон.','date'=>'27 сентября 2026','views'=>'892','class'=>'school'],
  ['title'=>'Мастера Унцукуля представили традиционные изделия','excerpt'=>'Народные художественные промыслы остаются одной из визитных карточек района.','date'=>'26 сентября 2026','views'=>'1 103','class'=>'craft'],
  ['title'=>'В районе прошли спортивные соревнования среди молодёжи','excerpt'=>'Команды из разных населённых пунктов встретились на районной площадке.','date'=>'25 сентября 2026','views'=>'764','class'=>'sport'],
  ['title'=>'Истории земляков: люди, которые сохраняют связь поколений','excerpt'=>'Рассказываем о жителях района, их труде и семейных традициях.','date'=>'24 сентября 2026','views'=>'621','class'=>'people'],
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
    <div class="hero-location" aria-label="География новости">
      <span data-hero-location-city><?=e($heroLocationCity)?></span>
      <b data-hero-location-region><?=e($heroLocationRegion)?></b>
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
                   data-hero-cover="<?=e(!empty($item['cover_image']) ? base_url($item['cover_image']) : '')?>"
                   data-hero-location-city="<?=e(trim((string)($item['location_city']??'')) ?: 'Унцукульский район')?>"
                   data-hero-location-region="<?=e(trim((string)($item['location_region']??'')) ?: 'Дагестан')?>">
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

<section class="content-section district-news-section district-magazine-style">
  <div class="block-heading district-magazine-heading">
    <div>
      <span class="heading-kicker">Район</span>
      <h2>Новости района</h2>
    </div>
    <a href="<?=e(isset($catBySlug['novosti-rayona']) ? category_url($catBySlug['novosti-rayona']) : base_url('news.php'))?>">Все новости →</a>
  </div>

  <?php if($districtNews):
    $lead = $districtNews[0];
    $secondary = array_slice($districtNews,1,4);
  ?>
    <div class="district-magazine-grid">
      <article class="district-magazine-lead">
        <a class="district-magazine-lead-image" href="<?=e(article_url($lead))?>">
          <?php if(!empty($lead['cover_image'])):?>
            <img src="<?=e(base_url($lead['cover_image']))?>" alt="<?=e($lead['title'])?>">
          <?php else:?>
            <span class="district-magazine-placeholder">АХИХЪАН</span>
          <?php endif;?>
        </a>

        <div class="district-magazine-lead-copy">
          <span class="district-magazine-category">Новости района</span>
          <h3><a href="<?=e(article_url($lead))?>"><?=e($lead['title'])?></a></h3>
          <time datetime="<?=e(date('Y-m-d',strtotime($lead['published_at'] ?: $lead['created_at'])))?>"><?=e(ru_date($lead['published_at'] ?: $lead['created_at']))?></time>
          <?php if(!empty($lead['excerpt'])):?><p><?=e($lead['excerpt'])?></p><?php endif;?>
        </div>
      </article>

      <div class="district-magazine-side-grid">
        <?php foreach($secondary as $item):?>
          <article class="district-magazine-card">
            <a class="district-magazine-card-image" href="<?=e(article_url($item))?>">
              <?php if(!empty($item['cover_image'])):?>
                <img src="<?=e(base_url($item['cover_image']))?>" alt="<?=e($item['title'])?>" loading="lazy">
              <?php else:?>
                <span class="district-magazine-placeholder">АХИХЪАН</span>
              <?php endif;?>
            </a>

            <div class="district-magazine-card-copy">
              <span class="district-magazine-category">Новости района</span>
              <h3><a href="<?=e(article_url($item))?>"><?=e($item['title'])?></a></h3>
              <time datetime="<?=e(date('Y-m-d',strtotime($item['published_at'] ?: $item['created_at'])))?>"><?=e(ru_date($item['published_at'] ?: $item['created_at']))?></time>
            </div>
          </article>
        <?php endforeach;?>
      </div>
    </div>
  <?php else:?>
    <div class="district-news-empty">
      <span class="heading-kicker">Новости района</span>
      <strong>В этой рубрике пока нет опубликованных материалов</strong>
      <p>Новости появятся здесь автоматически после публикации материала в рубрике «Новости района».</p>
    </div>
  <?php endif;?>
</section>

<section class="content-section regional-sport-sidebar-section">
  <div class="regional-sport-layout">
    <div class="regional-sport-main">

      <section class="home-news-row-section regional-news-card">
        <div class="block-heading district-magazine-heading home-news-row-heading">
          <div>
            <span class="heading-kicker">Регион</span>
            <h2>Региональные новости</h2>
          </div>
          <a href="<?=e(isset($catBySlug['regionalnye-novosti']) ? category_url($catBySlug['regionalnye-novosti']) : base_url('news.php'))?>">Все новости →</a>
        </div>

        <?php if($regionalNews):?>
          <div class="home-news-three-grid">
            <?php foreach(array_slice($regionalNews,0,3) as $item):?>
              <article class="home-news-three-card">
                <a class="home-news-three-image" href="<?=e(article_url($item))?>">
                  <?php if(!empty($item['cover_image'])):?>
                    <img src="<?=e(base_url($item['cover_image']))?>" alt="<?=e($item['title'])?>" loading="lazy">
                  <?php else:?>
                    <span class="home-news-three-placeholder">АХИХЪАН</span>
                  <?php endif;?>
                </a>
                <div class="home-news-three-copy">
                  <span class="article-label">Региональные новости</span>
                  <h4><a href="<?=e(article_url($item))?>"><?=e($item['title'])?></a></h4>
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

      <section class="home-news-row-section sport-news-card">
        <div class="block-heading district-magazine-heading home-news-row-heading">
          <div>
            <span class="heading-kicker">Спорт</span>
            <h2>Спортивные новости</h2>
          </div>
          <a href="<?=e(isset($catBySlug['sport']) ? category_url($catBySlug['sport']) : base_url('news.php'))?>">Все новости →</a>
        </div>

        <?php if($sportNews):?>
          <div class="home-news-three-grid">
            <?php foreach(array_slice($sportNews,0,3) as $item):?>
              <article class="home-news-three-card">
                <a class="home-news-three-image" href="<?=e(article_url($item))?>">
                  <?php if(!empty($item['cover_image'])):?>
                    <img src="<?=e(base_url($item['cover_image']))?>" alt="<?=e($item['title'])?>" loading="lazy">
                  <?php else:?>
                    <span class="home-news-three-placeholder">АХИХЪАН</span>
                  <?php endif;?>
                </a>
                <div class="home-news-three-copy">
                  <span class="article-label">Спорт</span>
                  <h4><a href="<?=e(article_url($item))?>"><?=e($item['title'])?></a></h4>
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

<section class="content-section categories-section home-video-showcase">
  <div class="block-heading">
    <div>
      <span class="heading-kicker">Видеогалерея</span>
      <h2>Последние видео</h2>
    </div>
    <a class="home-video-all" href="<?=e(base_url('videos.php'))?>">Все видео →</a>
  </div>

  <?php if($homeVideos):?>
    <div class="section-cards home-video-section-cards">
      <?php foreach($homeVideos as $i=>$video):?>
        <a class="section-card home-video-section-card" href="<?=e(base_url('videos.php?id='.$video['id']))?>">
          <span class="section-number"><?=str_pad((string)($i+1),2,'0',STR_PAD_LEFT)?></span>
          <div class="section-card-image home-video-section-image" <?php if(!empty($video['cover_image'])):?>style="background-image:url('<?=e(base_url($video['cover_image']))?>')"<?php endif;?>>
            <?php if(empty($video['cover_image'])):?>
              <span class="home-video-fallback">
                <i class="<?=($video['provider']??'')==='vk'?'fa-brands fa-vk':(($video['provider']??'')==='ok'?'fa-brands fa-odnoklassniki':'fa-solid fa-play')?>"></i>
              </span>
            <?php endif;?>
            <span class="home-video-play"><i class="fa-solid fa-play"></i></span>
          </div>
          <div class="section-card-body home-video-section-body">
            <small class="home-video-provider"><?=e(video_provider_label($video['provider']??null))?> · <?=e(ru_date($video['video_date']))?></small>
            <strong><?=e($video['title'])?></strong>
            <b>Смотреть видео →</b>
          </div>
        </a>
      <?php endforeach;?>
    </div>
  <?php else:?>
    <div class="documents-empty home-video-empty">
      <span class="document-format-icon file"><i class="fa-solid fa-video"></i></span>
      <div>
        <strong>Видео пока не опубликованы</strong>
        <p>После добавления материала в видеогалерею последние шесть видео появятся здесь автоматически.</p>
      </div>
    </div>
  <?php endif;?>
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
