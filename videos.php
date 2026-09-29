<?php
require __DIR__ . '/app/bootstrap.php';
if (!APP_INSTALLED) { header('Location: install.php'); exit; }

$videoId=(int)($_GET['id']??0);
$current=$videoId ? video_gallery_item($videoId,true) : null;

if($videoId && !$current){
  http_response_code(404);
  $pageTitle='Видео не найдено';
  $pageDescription='Запрошенное видео не найдено.';
  $seoRobots='noindex,nofollow,noarchive';
  require __DIR__.'/partials/header.php';
  ?>
  <div class="wrap video-page-shell">
    <section class="video-public-empty">
      <span class="heading-kicker">Видеогалерея</span>
      <h1>Видео не найдено</h1>
      <p>Возможно, материал ещё не опубликован или был удалён.</p>
      <a href="<?=e(base_url('videos.php'))?>">Все видео →</a>
    </section>
  </div>
  <?php
  require __DIR__.'/partials/footer.php';
  exit;
}

$videos=video_gallery_items(true);
$pageTitle=$current ? $current['title'] : 'Видеогалерея';
$pageDescription=$current
  ? (rich_text_excerpt($current['description'],260) ?: 'Видео «'.$current['title'].'» — АХИХЪАН.')
  : 'Видеогалерея сетевого издания АХИХЪАН: события, интервью, репортажи и жизнь Унцукульского района.';
$seoCanonical=$current ? base_url('videos.php?id='.(int)$current['id']) : base_url('videos.php');
$seoImage=($current && !empty($current['cover_image'])) ? (string)$current['cover_image'] : 'assets/img/akhikhan-logo-hq.webp';

require __DIR__.'/partials/header.php';
?>

<div class="wrap video-page-shell">
  <?php if($current):
    $embed=video_embed_url($current);
  ?>
    <section class="video-view-hero">
      <a class="video-back" href="<?=e(base_url('videos.php'))?>">← Вся видеогалерея</a>

      <div class="video-view-layout">
        <div class="video-view-player">
          <?php if(($current['source_type']??'')==='local' && !empty($current['video_file'])):?>
            <video controls playsinline preload="metadata" <?php if(!empty($current['cover_image'])):?>poster="<?=e(base_url($current['cover_image']))?>"<?php endif;?>>
              <source src="<?=e(base_url($current['video_file']))?>">
              Ваш браузер не поддерживает воспроизведение видео.
            </video>
          <?php elseif($embed!==''):?>
            <iframe
              src="<?=e($embed)?>"
              title="<?=e($current['title'])?>"
              allow="autoplay; encrypted-media; fullscreen; picture-in-picture"
              allowfullscreen
              loading="eager"
              referrerpolicy="strict-origin-when-cross-origin"></iframe>
          <?php else:?>
            <div class="video-player-unavailable">
              <i class="fa-solid fa-video-slash"></i>
              <strong>Не удалось открыть встроенный плеер</strong>
              <?php if(!empty($current['source_url'])):?><a href="<?=e($current['source_url'])?>" target="_blank" rel="noopener">Открыть видео на <?=e(video_provider_label($current['provider']))?> →</a><?php endif;?>
            </div>
          <?php endif;?>
        </div>

        <div class="video-view-copy">
          <span class="video-provider-pill"><?=e(video_provider_label($current['provider']))?></span>
          <h1><?=e($current['title'])?></h1>
          <time datetime="<?=e($current['video_date'])?>"><?=e(ru_date($current['video_date']))?></time>
          <?php if(!empty($current['description'])):?><div class="video-description rich-text"><?=rich_text_html($current['description'])?></div><?php endif;?>
          <?php if(($current['source_type']??'')==='external' && !empty($current['source_url'])):?>
            <a class="video-source-link" href="<?=e($current['source_url'])?>" target="_blank" rel="noopener">
              Открыть на <?=e(video_provider_label($current['provider']))?> <i class="fa-solid fa-arrow-up-right-from-square"></i>
            </a>
          <?php endif;?>
        </div>
      </div>
    </section>

    <?php
      $more=array_values(array_filter($videos,fn($v)=>(int)$v['id']!==(int)$current['id']));
      $more=array_slice($more,0,6);
    ?>
    <?php if($more):?>
      <section class="video-more-section">
        <div class="block-heading district-magazine-heading">
          <div>
            <span class="heading-kicker">Смотреть дальше</span>
            <h2>Другие видео</h2>
          </div>
        </div>

        <div class="video-gallery-grid">
          <?php foreach($more as $video):?>
            <a class="video-gallery-card" href="<?=e(base_url('videos.php?id='.$video['id']))?>">
              <span class="video-gallery-preview">
                <?php if(!empty($video['cover_image'])):?><img src="<?=e(base_url($video['cover_image']))?>" alt="<?=e($video['title'])?>" loading="lazy"><?php endif;?>
                <span class="video-gallery-play"><i class="fa-solid fa-play"></i></span>
                <span class="video-gallery-provider"><?=e(video_provider_label($video['provider']))?></span>
              </span>
              <span class="video-gallery-copy">
                <small><?=e(ru_date($video['video_date']))?></small>
                <strong><?=e($video['title'])?></strong>
              </span>
            </a>
          <?php endforeach;?>
        </div>
      </section>
    <?php endif;?>

  <?php else:?>
    <section class="video-page-heading">
      <div>
        <span class="heading-kicker">Медиа</span>
        <h1>Видеогалерея</h1>
        <p>Репортажи, события, интервью, спорт, культура и жизнь Унцукульского района в видеоформате.</p>
      </div>
      <span class="video-page-count"><?=e((string)count($videos))?> видео</span>
    </section>

    <?php if($videos):?>
      <section class="video-gallery-grid video-gallery-main-grid">
        <?php foreach($videos as $i=>$video):?>
          <a class="video-gallery-card <?=$i===0?'is-leading':''?>" href="<?=e(base_url('videos.php?id='.$video['id']))?>">
            <span class="video-gallery-preview">
              <?php if(!empty($video['cover_image'])):?>
                <img src="<?=e(base_url($video['cover_image']))?>" alt="<?=e($video['title'])?>" loading="<?=$i<3?'eager':'lazy'?>">
              <?php else:?>
                <span class="video-gallery-placeholder">
                  <i class="<?=$video['provider']==='vk'?'fa-brands fa-vk':($video['provider']==='ok'?'fa-brands fa-odnoklassniki':($video['provider']==='rutube'?'fa-solid fa-play':'fa-solid fa-video'))?>"></i>
                  <b><?=e(video_provider_label($video['provider']))?></b>
                </span>
              <?php endif;?>
              <span class="video-gallery-play"><i class="fa-solid fa-play"></i></span>
              <span class="video-gallery-provider"><?=e(video_provider_label($video['provider']))?></span>
            </span>
            <span class="video-gallery-copy">
              <small><?=e(ru_date($video['video_date']))?></small>
              <strong><?=e($video['title'])?></strong>
              <?php if(!empty($video['description'])):?><span><?=e(rich_text_excerpt($video['description'],180))?></span><?php endif;?>
              <b>Смотреть видео →</b>
            </span>
          </a>
        <?php endforeach;?>
      </section>
    <?php else:?>
      <div class="video-public-empty">
        <i class="fa-solid fa-video"></i>
        <strong>Видеогалерея пока пустая</strong>
        <p>После публикации видео в редакционной панели материалы автоматически появятся здесь.</p>
      </div>
    <?php endif;?>
  <?php endif;?>
</div>

<?php require __DIR__.'/partials/footer.php'; ?>
