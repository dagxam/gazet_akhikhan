<?php
require __DIR__ . '/app/bootstrap.php';
if (!APP_INSTALLED) { header('Location: install.php'); exit; }

$slug = trim($_GET['slug'] ?? '');
$q = db()->prepare("SELECT a.*,c.name category_name,c.slug category_slug,u.name author_name
                    FROM articles a
                    LEFT JOIN categories c ON c.id=a.category_id
                    LEFT JOIN users u ON u.id=a.author_id
                    WHERE a.slug=? AND a.status='published'
                      AND (a.published_at IS NULL OR a.published_at<=CURRENT_TIMESTAMP)
                    LIMIT 1");
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

if(should_count_article_view((int)$article['id'])){
  db()->prepare('UPDATE articles SET views=views+1 WHERE id=?')->execute([$article['id']]);
}
$related = latest_articles(5, (int)$article['id']);
$articleImages = article_images((int)$article['id']);
$reactionCounts = article_reaction_counts((int)$article['id']);
$pageBlocks = page_right_blocks(true);
$articleCategoryRows = article_categories((int)$article['id']);
$articleSections = [];
foreach($articleCategoryRows as $categoryRow){
  $name=trim((string)($categoryRow['name']??''));
  if($name!=='') $articleSections[]=$name;
}
if(!$articleSections && !empty($article['category_name'])) $articleSections[]=(string)$article['category_name'];
$articleSections=array_values(array_unique($articleSections));

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
$seoArticleImages=[$seoImageAbsolute];
foreach($articleImages as $extraImage){
  $extraPath=(string)($extraImage['image_path']??'');
  if($extraPath!=='') $seoArticleImages[]=base_url(ltrim($extraPath,'/'));
}
$seoArticleImages=array_values(array_unique($seoArticleImages));
$seoArticleSection = implode(', ', $articleSections);
$articlePlainText = trim(rich_text_plain((string)$article['content']));
$articleWordCount = $articlePlainText==='' ? 0 : count(preg_split('/\s+/u',$articlePlainText,-1,PREG_SPLIT_NO_EMPTY));

$articleJsonLd = [
  '@context' => 'https://schema.org',
  '@type' => 'NewsArticle',
  'mainEntityOfPage' => ['@type'=>'WebPage','@id'=>$seoCanonical],
  'url' => $seoCanonical,
  'headline' => (string)$article['title'],
  'description' => $pageDescription,
  'image' => $seoArticleImages,
  'thumbnailUrl' => $seoImageAbsolute,
  'datePublished' => $seoPublishedTime,
  'dateModified' => $seoModifiedTime,
  'author' => [[
    '@type' => !empty($article['author_name']) ? 'Person' : 'Organization',
    'name' => $seoAuthor,
  ]],
  'publisher' => ['@id'=>base_url('#organization')],
  'articleSection' => $articleSections ?: ['Новости'],
  'keywords' => implode(', ', $articleSections),
  'inLanguage' => 'ru-RU',
  'isAccessibleForFree' => true,
];
if($articleWordCount>0) $articleJsonLd['wordCount']=$articleWordCount;

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

$shareUrlEncoded=rawurlencode($seoCanonical);
$shareTitleEncoded=rawurlencode((string)$article['title']);
$shareTextEncoded=rawurlencode((string)$article['title'].' '.$seoCanonical);
$shareImageEncoded=rawurlencode($seoImageAbsolute);
$shareLinks=[
  'vk'=>'https://vk.com/share.php?url='.$shareUrlEncoded.'&title='.$shareTitleEncoded,
  'ok'=>'https://connect.ok.ru/offer?url='.$shareUrlEncoded.'&title='.$shareTitleEncoded.'&imageUrl='.$shareImageEncoded,
  'max'=>'https://max.ru/:share?text='.$shareTextEncoded,
  'telegram'=>'https://t.me/share/url?url='.$shareUrlEncoded.'&text='.$shareTitleEncoded,
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

      <?php if($articleImages):?>
        <section class="article-extra-gallery" aria-label="Фотографии к новости">
          <div class="article-extra-gallery-head">
            <span class="heading-kicker">Фотографии</span>
            <h2>К материалу</h2>
          </div>
          <div class="article-extra-gallery-grid items-<?=e((string)min(6,count($articleImages)))?>">
            <?php foreach($articleImages as $index=>$image):?>
              <a class="article-extra-photo <?=$index===0?'is-featured':''?>" href="<?=e(base_url($image['image_path']))?>" target="_blank" rel="noopener">
                <img src="<?=e(base_url($image['image_path']))?>" alt="<?=e($image['caption'] ?: $article['title'])?>" loading="<?=$index<2?'eager':'lazy'?>">
                <?php if(!empty($image['caption'])):?><span><?=e($image['caption'])?></span><?php endif;?>
              </a>
            <?php endforeach;?>
          </div>
        </section>
      <?php endif;?>

      <section class="article-community-card">
        <div class="article-rating" data-article-reaction-widget data-article-id="<?=$article['id']?>" data-endpoint="<?=e(base_url('article-reaction.php'))?>">
          <div class="article-rating-copy">
            <span class="heading-kicker">Оценка читателей</span>
            <strong>Была полезна эта новость?</strong>
          </div>
          <div class="article-rating-actions">
            <button type="button" class="article-reaction-button is-like" data-reaction="like" aria-label="Нравится">
              <i class="fa-regular fa-thumbs-up"></i>
              <span>Нравится</span>
              <b data-reaction-count="like"><?=number_format((int)$reactionCounts['likes'],0,'.',' ')?></b>
            </button>
            <button type="button" class="article-reaction-button is-dislike" data-reaction="dislike" aria-label="Не нравится">
              <i class="fa-regular fa-thumbs-down"></i>
              <span>Не нравится</span>
              <b data-reaction-count="dislike"><?=number_format((int)$reactionCounts['dislikes'],0,'.',' ')?></b>
            </button>
          </div>
        </div>

        <div class="article-share">
          <div class="article-share-copy">
            <span class="heading-kicker">Поделиться</span>
            <strong>Отправить новость</strong>
          </div>
          <div class="article-share-actions">
            <a class="article-share-button is-vk" href="<?=e($shareLinks['vk'])?>" target="_blank" rel="noopener" aria-label="Поделиться ВКонтакте"><i class="fa-brands fa-vk"></i><span>VK</span></a>
            <a class="article-share-button is-ok" href="<?=e($shareLinks['ok'])?>" target="_blank" rel="noopener" aria-label="Поделиться в Одноклассниках"><i class="fa-brands fa-odnoklassniki"></i><span>ОК</span></a>
            <a class="article-share-button is-max" href="<?=e($shareLinks['max'])?>" target="_blank" rel="noopener" aria-label="Поделиться в MAX"><img src="<?=e(base_url('assets/img/social-max-white.svg'))?>" alt=""><span>MAX</span></a>
            <a class="article-share-button is-telegram" href="<?=e($shareLinks['telegram'])?>" target="_blank" rel="noopener" aria-label="Поделиться в Telegram"><i class="fa-brands fa-telegram"></i><span>Telegram</span></a>
            <button class="article-share-button is-copy" type="button" data-copy-article-link="<?=e($seoCanonical)?>" aria-label="Скопировать ссылку"><i class="fa-solid fa-link"></i><span>Ссылка</span></button>
          </div>
        </div>
      </section>
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
        <?php $pageBlockId='page-right-block-'.(int)$block['id']; ?>
        <?php if(!empty($block['image'])):?><style nonce="<?=e(csp_nonce())?>">#<?=e($pageBlockId)?>{--right-block-image:<?=e(css_url_literal(base_url($block['image'])))?>}</style><?php endif;?>
        <section id="<?=e($pageBlockId)?>" class="right-feature-card right-feature-<?=e($blockStyle)?> <?=!empty($block['image'])?'has-image':''?>">
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
