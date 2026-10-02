<?php
require __DIR__ . '/app/bootstrap.php';
if (!APP_INSTALLED) { header('Location: install.php'); exit; }

$newsTotal=(int)db()->query("SELECT COUNT(*) FROM articles WHERE status='published' AND (published_at IS NULL OR published_at<=CURRENT_TIMESTAMP)")->fetchColumn();
$newsPager=public_pagination_state($newsTotal,18,'page');
$newsLimit=(int)$newsPager['per_page'];
$newsOffset=(int)$newsPager['offset'];

$q=db()->query("SELECT a.*,c.name category_name,c.slug category_slug
FROM articles a LEFT JOIN categories c ON c.id=a.category_id
WHERE a.status='published'
AND (a.published_at IS NULL OR a.published_at<=CURRENT_TIMESTAMP)
ORDER BY COALESCE(a.published_at,a.created_at) DESC,id DESC
LIMIT ".$newsLimit." OFFSET ".$newsOffset);
$articles=$q->fetchAll();

$pageTitle='Новости Унцукульского района';
$pageDescription='Последние новости Унцукульского района Республики Дагестан: события, общество, культура, спорт и важные публикации сетевого издания «АХИХЪАН».';
$seoCanonical=public_pagination_url('news.php',$newsPager['page']);
$newsItemList=[];
foreach(array_slice($articles,0,20) as $index=>$item){
  $newsItemList[]=[
    '@type'=>'ListItem',
    'position'=>$index+1,
    'url'=>article_url($item),
    'name'=>(string)$item['title'],
  ];
}
$seoJsonLd=[[
  '@context'=>'https://schema.org',
  '@type'=>'CollectionPage',
  '@id'=>$seoCanonical.'#collection',
  'url'=>$seoCanonical,
  'name'=>$pageTitle.' — АХИХЪАН',
  'description'=>$pageDescription,
  'isPartOf'=>['@id'=>base_url('#website')],
  'mainEntity'=>[
    '@type'=>'ItemList',
    'numberOfItems'=>$newsTotal,
    'itemListElement'=>$newsItemList,
  ],
]];
require __DIR__.'/partials/header.php';
?>
<div class="wrap page-shell">
  <div class="page-head">
    <div>
      <span class="heading-kicker">АХИХЪАН</span>
      <h1>Новости района</h1>
      <p>События, люди и темы, важные для Унцукульского района Республики Дагестан.</p>
    </div>
    <div class="page-head-mark" aria-hidden="true"></div>
  </div>

  <?php if($articles): ?>
  <section class="listing-grid">
    <?php foreach($articles as $a): ?>
      <article class="list-card">
        <a class="list-thumb<?=empty($a['cover_image'])?' demo-road':''?>" href="<?=e(article_url($a))?>" style="<?=!empty($a['cover_image']) ? "background-image:url('".e(base_url($a['cover_image']))."')" : ''?>"></a>
        <div class="list-card-body">
          <div class="article-label"><?=e($a['category_name'] ?: 'Новости')?></div>
          <h2><a href="<?=e(article_url($a))?>"><?=e($a['title'])?></a></h2>
          <p><?=e(rich_text_excerpt($a['excerpt'],220))?></p>
          <div class="article-meta"><span><?=e(ru_date($a['published_at'] ?: $a['created_at']))?></span><span>◉ <?=number_format((int)$a['views'],0,'.',' ')?></span></div>
        </div>
      </article>
    <?php endforeach; ?>
  </section>
  <?php render_public_pagination('news.php',$newsPager['page'],$newsPager['total_pages'],[],'page','Страницы новостей'); ?>
  <?php else: ?>
    <div class="empty">Пока нет опубликованных новостей. После публикации материалов они появятся здесь автоматически.</div>
  <?php endif; ?>
</div>
<?php require __DIR__.'/partials/footer.php'; ?>
