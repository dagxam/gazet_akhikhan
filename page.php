<?php
require __DIR__ . '/app/bootstrap.php';
if (!APP_INSTALLED) { header('Location: install.php'); exit; }

$slug=trim($_GET['slug']??'');
$page=static_page_by_slug($slug,true);

if(!$page){
  http_response_code(404);
  $pageTitle='Страница не найдена';
  $pageDescription='Запрошенная страница не найдена.';
  $seoRobots='noindex,nofollow,noarchive';
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
$pageRelated=latest_articles(5);
$pageTitle=$page['title'];
$pageDescription=rich_text_excerpt($page['excerpt']??'',260)
  ?: rich_text_excerpt($page['content']??'',260)
  ?: 'Страница сетевого издания «АХИХЪАН».';
$seoCanonical=static_page_url($page);
$seoImage=!empty($page['cover_image']) ? (string)$page['cover_image'] : 'assets/img/akhikhan-logo-hq.webp';
$seoJsonLd=[[
  '@context'=>'https://schema.org',
  '@type'=>'BreadcrumbList',
  'itemListElement'=>[
    ['@type'=>'ListItem','position'=>1,'name'=>'Главная','item'=>base_url()],
    ['@type'=>'ListItem','position'=>2,'name'=>(string)$page['title'],'item'=>$seoCanonical],
  ],
]];
require __DIR__.'/partials/header.php';
?>

<div class="wrap static-public-shell">
  <div class="static-public-layout has-sidebar">
    <article class="static-page-paper">
      <div class="article-breadcrumbs">
        <a href="<?=e(base_url())?>">Главная</a><span>›</span><span><?=e($page['title'])?></span>
      </div>

      <span class="heading-kicker">АХИХЪАН</span>
      <h1><?=e($page['title'])?></h1>
      <?php if(!empty($page['excerpt'])):?><div class="static-page-lead rich-text"><?=rich_text_html($page['excerpt'])?></div><?php endif;?>
      <?php if(!empty($page['cover_image'])):?><img class="static-page-cover" src="<?=e(base_url($page['cover_image']))?>" alt="<?=e($page['title'])?>"><?php endif;?>
      <div class="static-page-content rich-text"><?=rich_text_html($page['content'])?></div>
    </article>

    <aside class="static-page-sidebar">
      <section class="article-side-card">
        <h3>Читайте также</h3>
        <div class="related-list">
          <?php foreach($pageRelated as $item): ?>
            <a href="<?=e(article_url($item))?>"><?=e($item['title'])?></a>
          <?php endforeach; ?>
          <?php if(!$pageRelated): ?><a href="<?=e(base_url('news.php'))?>">Все новости Унцукульского района →</a><?php endif; ?>
        </div>
      </section>

      <section class="article-side-card">
        <h3>АХИХЪАН</h3>
        <div class="related-list">
          <a href="<?=e(base_url('about.php'))?>">О сетевом издании</a>
          <a href="<?=e(base_url('contacts.php'))?>">Связаться с редакцией</a>
          <a href="<?=e(nav_link_for_slug('istoriya','История'))?>">История района</a>
        </div>
      </section>

      <?php foreach($pageBlocks as $block):
        $blockHref=homepage_right_block_href($block['link_url']??'');
        $blockStyle=in_array($block['style'],['light','accent','dark'],true)?$block['style']:'light';
      ?>
        <section class="right-feature-card right-feature-<?=e($blockStyle)?> <?=!empty($block['image'])?'has-image':''?>" <?php if(!empty($block['image'])):?>style="--right-block-image:url('<?=e(base_url($block['image']))?>')"<?php endif;?>>
          <div class="right-feature-overlay"></div>
          <div class="right-feature-content">
            <?php if(!empty($block['kicker'])):?><span class="heading-kicker"><?=e($block['kicker'])?></span><?php endif;?>
            <h3><?=e($block['title'])?></h3>
            <?php if(!empty($block['body'])):?><div class="right-feature-body rich-text"><?=rich_text_html($block['body'])?></div><?php endif;?>
            <?php if($blockHref!=='' && !empty($block['link_text'])):?><a href="<?=e($blockHref)?>"><?=e($block['link_text'])?> <b>→</b></a><?php endif;?>
          </div>
        </section>
      <?php endforeach;?>
    </aside>
  </div>
</div>

<?php require __DIR__.'/partials/footer.php'; ?>
