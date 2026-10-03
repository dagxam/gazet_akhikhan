<?php
require __DIR__ . '/app/bootstrap.php';
if (!APP_INSTALLED) { header('Location: install.php'); exit; }

$albumId=(int)($_GET['album']??0);
$album=$albumId ? photo_album($albumId,true) : null;

if($albumId && !$album){
  http_response_code(404);
  $pageTitle='Фотоальбом не найден';
  $pageDescription='Запрошенный фотоальбом не найден.';
  $seoRobots='noindex,nofollow,noarchive';
  require __DIR__.'/partials/header.php';
  ?>
  <div class="wrap gallery-page-shell">
    <section class="gallery-not-found">
      <span class="heading-kicker">Фотогалерея</span>
      <h1>Фотоальбом не найден</h1>
      <p>Возможно, он был перемещён или ещё не опубликован.</p>
      <a href="<?=e(base_url('gallery.php'))?>">Все фотоальбомы →</a>
    </section>
  </div>
  <?php
  require __DIR__.'/partials/footer.php';
  exit;
}

$albums=photo_albums(true);
$photos=$album ? photo_album_images((int)$album['id']) : [];

$pageTitle=$album ? $album['title'] : 'Фотогалерея';
$pageDescription=$album
  ? (rich_text_excerpt($album['description'],260) ?: 'Фотоальбом «'.$album['title'].'» — АХИХЪАН.')
  : 'Фотогалерея сетевого издания АХИХЪАН: события, люди и жизнь Унцукульского района.';
$seoCanonical=$album ? base_url('gallery.php?album='.(int)$album['id']) : base_url('gallery.php');

require __DIR__.'/partials/header.php';
?>

<div class="wrap gallery-page-shell">
  <?php if($album):?>
    <section class="gallery-album-hero">
      <div>
        <a class="gallery-back" href="<?=e(base_url('gallery.php'))?>">← Все фотоальбомы</a>
        <span class="heading-kicker">Фотоальбом</span>
        <h1><?=e($album['title'])?></h1>
        <?php if(!empty($album['description'])):?><div class="gallery-album-description rich-text"><?=rich_text_html($album['description'])?></div><?php endif;?>
        <div class="gallery-album-hero-meta">
          <span><?=e(ru_date($album['album_date']))?></span>
          <span><?=e((string)$album['photo_count'])?> фотографий</span>
        </div>
      </div>
      <?php if(!empty($album['cover_image'])):?>
        <?php $albumHeroClass=csp_dynamic_class("background-image:url('".base_url($album['cover_image'])."');",'gallery-hero'); ?>
        <div class="gallery-album-hero-cover <?=e($albumHeroClass)?>"></div>
      <?php endif;?>
    </section>

    <?php if($photos):?>
      <section class="gallery-photo-grid" aria-label="<?=e($album['title'])?>">
        <?php foreach($photos as $i=>$photo):?>
          <button class="gallery-photo-tile <?=$i===0?'is-featured':''?>" type="button"
            data-gallery-lightbox
            data-full="<?=e(base_url($photo['image_path']))?>"
            data-caption="<?=e($photo['caption']??'')?>"
            aria-label="Открыть фотографию <?=e((string)($i+1))?>">
            <img src="<?=e(base_url($photo['image_path']))?>" alt="<?=e($photo['caption'] ?: $album['title'])?>" loading="<?=$i<4?'eager':'lazy'?>">
            <?php if(!empty($photo['caption'])):?><span><?=e($photo['caption'])?></span><?php endif;?>
          </button>
        <?php endforeach;?>
      </section>
    <?php else:?>
      <div class="gallery-public-empty">
        <strong>Фотографии скоро появятся</strong>
        <p>Альбом уже опубликован, но фотографии в него ещё не добавлены.</p>
      </div>
    <?php endif;?>

  <?php else:?>
    <section class="gallery-page-heading">
      <div>
        <span class="heading-kicker">Медиа</span>
        <h1>Фотогалерея</h1>
        <p>События района, люди, традиции, спорт и важные моменты — в фотографиях редакции «АХИХЪАН».</p>
      </div>
      <span class="gallery-page-count"><?=e((string)count($albums))?> альбомов</span>
    </section>

    <?php if($albums):?>
      <section class="gallery-albums-public-grid">
        <?php foreach($albums as $i=>$item):?>
          <a class="gallery-album-card <?=$i===0?'is-leading':''?>" href="<?=e(base_url('gallery.php?album='.$item['id']))?>">
            <?php $albumCardClass=!empty($item['cover_image']) ? csp_dynamic_class("background-image:url('".base_url($item['cover_image'])."');",'gallery-card') : ''; ?>
            <span class="gallery-album-card-image <?=e($albumCardClass)?>">
              <i><?=e((string)$item['photo_count'])?> фото</i>
            </span>
            <span class="gallery-album-card-copy">
              <small><?=e(ru_date($item['album_date']))?></small>
              <strong><?=e($item['title'])?></strong>
              <?php if(!empty($item['description'])):?><span><?=e(rich_text_excerpt($item['description'],180))?></span><?php endif;?>
              <b>Открыть альбом →</b>
            </span>
          </a>
        <?php endforeach;?>
      </section>
    <?php else:?>
      <div class="gallery-public-empty">
        <strong>Фотоальбомов пока нет</strong>
        <p>Когда редакция опубликует первый фотоальбом, он появится на этой странице.</p>
      </div>
    <?php endif;?>
  <?php endif;?>
</div>

<div class="gallery-lightbox" data-gallery-lightbox-modal hidden>
  <button class="gallery-lightbox-close" type="button" data-gallery-lightbox-close aria-label="Закрыть">×</button>
  <button class="gallery-lightbox-nav prev" type="button" data-gallery-prev aria-label="Предыдущее фото">‹</button>
  <figure>
    <img src="" alt="" data-gallery-lightbox-image>
    <figcaption data-gallery-lightbox-caption></figcaption>
  </figure>
  <button class="gallery-lightbox-nav next" type="button" data-gallery-next aria-label="Следующее фото">›</button>
</div>

<script nonce="<?=e(csp_nonce())?>">
(function(){
  const items=[...document.querySelectorAll('[data-gallery-lightbox]')];
  const modal=document.querySelector('[data-gallery-lightbox-modal]');
  if(!items.length||!modal) return;

  const image=modal.querySelector('[data-gallery-lightbox-image]');
  const caption=modal.querySelector('[data-gallery-lightbox-caption]');
  let current=0;

  function show(index){
    current=(index+items.length)%items.length;
    const item=items[current];
    image.src=item.dataset.full||'';
    image.alt=item.dataset.caption||'Фотография';
    caption.textContent=item.dataset.caption||'';
    caption.hidden=!item.dataset.caption;
    modal.hidden=false;
    document.body.classList.add('gallery-lightbox-open');
  }

  function close(){
    modal.hidden=true;
    image.src='';
    document.body.classList.remove('gallery-lightbox-open');
  }

  items.forEach((item,index)=>item.addEventListener('click',()=>show(index)));
  modal.querySelector('[data-gallery-lightbox-close]')?.addEventListener('click',close);
  modal.querySelector('[data-gallery-prev]')?.addEventListener('click',()=>show(current-1));
  modal.querySelector('[data-gallery-next]')?.addEventListener('click',()=>show(current+1));
  modal.addEventListener('click',e=>{ if(e.target===modal) close(); });
  document.addEventListener('keydown',e=>{
    if(modal.hidden) return;
    if(e.key==='Escape') close();
    if(e.key==='ArrowLeft') show(current-1);
    if(e.key==='ArrowRight') show(current+1);
  });
})();
</script>

<?php require __DIR__.'/partials/footer.php'; ?>
