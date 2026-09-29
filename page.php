<?php
require __DIR__ . '/app/bootstrap.php';
if (!APP_INSTALLED) { header('Location: install.php'); exit; }

$slug=trim($_GET['slug']??'');
$page=static_page_by_slug($slug,true);

if(!$page){
  http_response_code(404);
  $pageTitle='Страница не найдена';
  require __DIR__.'/partials/header.php';
  ?>
  <div class="wrap page-shell static-public-shell">
    <section class="static-page-not-found">
      <span class="heading-kicker">404</span>
      <h1>Страница не найдена</h1>
      <p>Возможно, страница ещё не опубликована или была перемещена.</p>
      <a href="<?=e(base_url())?>">← На главную</a>
    </section>
  </div>
  <?php
  require __DIR__.'/partials/footer.php';
  exit;
}

$pageBlocks=page_right_blocks(true);
$pageTitle=$page['title'];
$pageDescription=$page['excerpt']??'';
require __DIR__.'/partials/header.php';
?>

<div class="wrap static-public-shell">
  <div class="static-public-layout <?=$pageBlocks?'has-sidebar':'no-sidebar'?>">
    <article class="static-page-paper">
      <div class="article-breadcrumbs">
        <a href="<?=e(base_url())?>">Главная</a><span>›</span><span><?=e($page['title'])?></span>
      </div>

      <span class="heading-kicker">АХИХЪАН</span>
      <h1><?=e($page['title'])?></h1>
      <?php if(!empty($page['excerpt'])):?><p class="static-page-lead"><?=e($page['excerpt'])?></p><?php endif;?>
      <?php if(!empty($page['cover_image'])):?><img class="static-page-cover" src="<?=e(base_url($page['cover_image']))?>" alt="<?=e($page['title'])?>"><?php endif;?>
      <div class="static-page-content"><?=nl2br(e($page['content']))?></div>
    </article>

    <?php if($pageBlocks):?>
      <aside class="static-page-sidebar">
        <?php foreach($pageBlocks as $block):
          $blockHref=homepage_right_block_href($block['link_url']??'');
          $blockStyle=in_array($block['style'],['light','accent','dark'],true)?$block['style']:'light';
        ?>
          <section class="right-feature-card right-feature-<?=e($blockStyle)?> <?=!empty($block['image'])?'has-image':''?>" <?php if(!empty($block['image'])):?>style="--right-block-image:url('<?=e(base_url($block['image']))?>')"<?php endif;?>>
            <div class="right-feature-overlay"></div>
            <div class="right-feature-content">
              <?php if(!empty($block['kicker'])):?><span class="heading-kicker"><?=e($block['kicker'])?></span><?php endif;?>
              <h3><?=e($block['title'])?></h3>
              <?php if(!empty($block['body'])):?><p><?=nl2br(e($block['body']))?></p><?php endif;?>
              <?php if($blockHref!=='' && !empty($block['link_text'])):?><a href="<?=e($blockHref)?>"><?=e($block['link_text'])?> <b>→</b></a><?php endif;?>
            </div>
          </section>
        <?php endforeach;?>
      </aside>
    <?php endif;?>
  </div>
</div>

<?php require __DIR__.'/partials/footer.php'; ?>
