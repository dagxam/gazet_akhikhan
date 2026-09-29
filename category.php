<?php
require __DIR__ . '/app/bootstrap.php';
if (!APP_INSTALLED) { header('Location: install.php'); exit; }

$slug = trim($_GET['slug'] ?? '');
$q = db()->prepare('SELECT * FROM categories WHERE slug=? AND is_active=1 LIMIT 1');
$q->execute([$slug]);
$category=$q->fetch();

if(!$category){
  http_response_code(404);
  $pageTitle='Рубрика не найдена';
  $pageDescription='Запрошенная рубрика не найдена.';
  $seoRobots='noindex,nofollow,noarchive';
  require __DIR__.'/partials/header.php';
  echo '<div class="wrap page-shell"><div class="page-head"><div><span class="heading-kicker">404</span><h1>Рубрика не найдена</h1></div><div class="page-head-mark"></div></div></div>';
  require __DIR__.'/partials/footer.php';
  exit;
}

$q=db()->prepare("SELECT a.*,? AS category_name,? AS category_slug
FROM articles a
INNER JOIN article_categories ac ON ac.article_id=a.id
WHERE ac.category_id=? AND a.status='published'
AND (a.published_at IS NULL OR a.published_at<=CURRENT_TIMESTAMP)
ORDER BY COALESCE(a.published_at,a.created_at) DESC");
$q->execute([$category['name'],$category['slug'],$category['id']]);
$articles=$q->fetchAll();

$pageTitle=$category['name'];
$pageDescription=rich_text_excerpt($category['description']??'',260) ?: 'Публикации рубрики «'.$category['name'].'» сетевого издания «АХИХЪАН».';
$seoCanonical=category_url($category);
$seoJsonLd=[[
  '@context'=>'https://schema.org',
  '@type'=>'BreadcrumbList',
  'itemListElement'=>[
    ['@type'=>'ListItem','position'=>1,'name'=>'Главная','item'=>base_url()],
    ['@type'=>'ListItem','position'=>2,'name'=>(string)$category['name'],'item'=>$seoCanonical],
  ],
]];
require __DIR__.'/partials/header.php';
?>
<div class="wrap page-shell">
  <div class="page-head">
    <div>
      <span class="heading-kicker">Рубрика</span>
      <h1><?=e($category['name'])?></h1>
      <p><?=e(rich_text_excerpt($category['description'] ?: 'Материалы сетевого издания «АХИХЪАН» о жизни Унцукульского района.',300))?></p>
    </div>
    <div class="page-head-mark" aria-hidden="true"></div>
  </div>

  <?php if($articles): ?>
  <section class="listing-grid">
    <?php foreach($articles as $a): ?>
      <article class="list-card">
        <a class="list-thumb<?=empty($a['cover_image'])?' demo-road':''?>" href="<?=e(article_url($a))?>" style="<?=!empty($a['cover_image']) ? "background-image:url('".e(base_url($a['cover_image']))."')" : ''?>"></a>
        <div class="list-card-body">
          <div class="article-label"><?=e($a['category_name'] ?: $category['name'])?></div>
          <h2><a href="<?=e(article_url($a))?>"><?=e($a['title'])?></a></h2>
          <p><?=e(rich_text_excerpt($a['excerpt'],220))?></p>
          <div class="article-meta">
            <span><?=e(ru_date($a['published_at'] ?: $a['created_at']))?></span>
            <span>◉ <?=number_format((int)$a['views'],0,'.',' ')?></span>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </section>
  <?php else: ?>
    <div class="empty">В этой рубрике пока нет опубликованных материалов.</div>
  <?php endif; ?>
</div>
<?php require __DIR__.'/partials/footer.php'; ?>
