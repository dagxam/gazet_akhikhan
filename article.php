<?php
require __DIR__ . '/app/bootstrap.php';
if (!APP_INSTALLED) { header('Location: install.php'); exit; }

$slug = trim($_GET['slug'] ?? '');
$q = db()->prepare("SELECT a.*,c.name category_name,c.slug category_slug,u.name author_name
                    FROM articles a
                    LEFT JOIN categories c ON c.id=a.category_id
                    LEFT JOIN users u ON u.id=a.author_id
                    WHERE a.slug=? AND a.status='published' LIMIT 1");
$q->execute([$slug]);
$article = $q->fetch();

if(!$article){
  http_response_code(404);
  $pageTitle='Материал не найден';
  $pageDescription='Запрошенный материал не найден.';
  $seoRobots='noindex,nofollow,noarchive';
  require __DIR__.'/partials/header.php';
  echo '<div class="wrap page-shell"><div class="page-head"><div><span class="heading-kicker">404</span><h1>Материал не найден</h1><p>Возможно, публикация была перемещена или ещё не опубликована.</p></div><div class="page-head-mark"></div></div><div class="empty"><a href="'.e(base_url('news.php')).'">← Вернуться к новостям</a></div></div>';
  require __DIR__.'/partials/footer.php';
  exit;
}

db()->prepare('UPDATE articles SET views=views+1 WHERE id=?')->execute([$article['id']]);
$related = latest_articles(5, (int)$article['id']);
$pageBlocks = page_right_blocks(true);

$pageTitle = $article['title'];
$pageDescription = rich_text_excerpt($article['excerpt'],260)
    ?: rich_text_excerpt($article['content'],260)
    ?: 'Материал сетевого издания «АХИХЪАН» об Унцукульском районе.';
$seoCanonical = article_url($article);
$seoType = 'article';
$seoImage = !empty($article['cover_image']) ? (string)$article['cover_image'] : 'assets/img/akhikhan-logo-hq.webp';
$seoAuthor = trim((string)($article['author_name'] ?: 'Редакция «АХИХЪАН»'));
$publishedRaw = (string)($article['published_at'] ?: $article['created_at']);
$modifiedRaw = (string)($article['updated_at'] ?: $publishedRaw);
$seoPublishedTime = strtotime($publishedRaw) ? date('c', strtotime($publishedRaw)) : '';
$seoModifiedTime = strtotime($modifiedRaw) ? date('c', strtotime($modifiedRaw)) : $seoPublishedTime;
$seoImageAbsolute = preg_match('~^https?://~i',$seoImage) ? $seoImage : base_url(ltrim($seoImage,'/'));

$articleJsonLd = [
  '@context' => 'https://schema.org',
  '@type' => 'NewsArticle',
  'mainEntityOfPage' => ['@type'=>'WebPage','@id'=>$seoCanonical],
  'headline' => (string)$article['title'],
  'description' => $pageDescription,
  'image' => [$seoImageAbsolute],
  'datePublished' => $seoPublishedTime,
  'dateModified' => $seoModifiedTime,
  'author' => [[
    '@type' => !empty($article['author_name']) ? 'Person' : 'Organization',
    'name' => $seoAuthor,
  ]],
  'publisher' => [
    '@type' => 'NewsMediaOrganization',
    'name' => 'АХИХЪАН',
    'url' => base_url(),
    'logo' => [
      '@type' => 'ImageObject',
      'url' => base_url('assets/img/akhikhan-logo-transparent.webp'),
    ],
  ],
  'articleSection' => (string)($article['category_name'] ?: 'Новости'),
  'inLanguage' => 'ru-RU',
  'isAccessibleForFree' => true,
];

$breadcrumbItems = [
  ['@type'=>'ListItem','position'=>1,'name'=>'Главная','item'=>base_url()],
  ['@type'=>'ListItem','position'=>2,'name'=>'Новости','item'=>base_url('news.php')],
];
if(!empty($article['category_name']) && !empty($article['category_slug'])){
  $breadcrumbItems[]=[
    '@type'=>'ListItem',
    'position'=>3,
    'name'=>(string)$article['category_name'],
    'item'=>base_url('category/'.rawurlencode((string)$article['category_slug'])),
  ];
}
$breadcrumbItems[]=[
  '@type'=>'ListItem',
  'position'=>count($breadcrumbItems)+1,
  'name'=>(string)$article['title'],
  'item'=>$seoCanonical,
];

$seoJsonLd = [
  $articleJsonLd,
  [
    '@context'=>'https://schema.org',
    '@type'=>'BreadcrumbList',
    'itemListElement'=>$breadcrumbItems,
  ],
];
require __DIR__ . '/partials/header.php';
?>
<div class="wrap page-shell">
  <div class="article-layout">
    <article class="article-paper">
      <div class="article-breadcrumbs">
        <a href="<?=e(base_url())?>">Главная</a><span>›</span>
        <a href="<?=e(base_url('news.php'))?>">Новости</a><span>›</span>
        <?php if($article['category_name']): ?><span><?=e($article['category_name'])?></span><?php endif; ?>
      </div>

      <div class="article-kicker"><?=e($article['category_name'] ?: 'Новости района')?></div>
      <h1><?=e($article['title'])?></h1>
      <div class="article-meta">
        <span><?=e(ru_date($article['published_at'] ?: $article['created_at']))?></span>
        <span><?=e($article['author_name'] ?: 'Редакция «АХИХЪАН»')?></span>
        <span>◉ <?=number_format((int)$article['views']+1,0,'.',' ')?></span>
      </div>

      <?php if($article['cover_image']): ?>
        <img class="article-cover" src="<?=e(base_url($article['cover_image']))?>" alt="<?=e($article['title'])?>">
      <?php endif; ?>

      <?php if($article['excerpt']): ?><div class="article-lead rich-text"><?=rich_text_html($article['excerpt'])?></div><?php endif; ?>
      <div class="article-content rich-text"><?=rich_text_html($article['content'])?></div>
    </article>

    <aside class="article-sidebar">
      <section class="article-side-card">
        <h3>Читайте также</h3>
        <div class="related-list">
          <?php foreach($related as $item): ?>
            <a href="<?=e(article_url($item))?>"><?=e($item['title'])?></a>
          <?php endforeach; ?>
          <?php if(!$related): ?><a href="<?=e(base_url('news.php'))?>">Все новости Унцукульского района →</a><?php endif; ?>
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
<?php require __DIR__ . '/partials/footer.php'; ?>
