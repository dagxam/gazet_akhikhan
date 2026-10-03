<?php
require __DIR__ . '/app/bootstrap.php';
if (!APP_INSTALLED) { header('Location: install.php'); exit; }

$term=trim((string)($_GET['q']??''));
if(function_exists('mb_substr')) $term=mb_substr($term,0,80,'UTF-8');
else $term=substr($term,0,80);
$term=preg_replace('/[%_]+/u',' ',$term) ?? $term;
$term=preg_replace('/\s+/u',' ',trim($term)) ?? trim($term);

$articles=[];
$searchError='';
$termLength=function_exists('mb_strlen') ? mb_strlen($term,'UTF-8') : strlen($term);

if($term!==''){
  if($termLength<2){
    $searchError='Введите не менее двух символов.';
  }else{
    $rate=security_rate_limit('public-search',security_client_ip(),25,60,60,true);
    if(!empty($rate['blocked'])){
      http_response_code(429);
      header('Retry-After: '.max(1,(int)$rate['remaining']));
      $searchError='Слишком много поисковых запросов. Повторите немного позже.';
    }else{
      $q=db()->prepare("SELECT a.*,c.name category_name,c.slug category_slug
      FROM articles a LEFT JOIN categories c ON c.id=a.category_id
      WHERE a.status='published'
        AND (a.published_at IS NULL OR a.published_at<=CURRENT_TIMESTAMP)
        AND (a.title LIKE ? OR a.excerpt LIKE ? OR a.content LIKE ?)
      ORDER BY COALESCE(a.published_at,a.created_at) DESC LIMIT 30");
      $like='%'.$term.'%';
      $q->execute([$like,$like,$like]);
      $articles=$q->fetchAll();
    }
  }
}

$pageTitle='Поиск';
$pageDescription='Поиск по публикациям сетевого издания «АХИХЪАН».';
$seoCanonical=base_url('search.php');
$seoRobots='noindex,follow,noarchive';
require __DIR__.'/partials/header.php';
?>
<div class="wrap page-shell">
  <div class="page-head">
    <div>
      <span class="heading-kicker">Архив издания</span>
      <h1>Поиск</h1>
      <p><?= $term!=='' ? 'Результаты по запросу «'.e($term).'»' : 'Найдите публикации по заголовку, анонсу или тексту материала.' ?></p>
    </div>
    <div class="page-head-mark" aria-hidden="true"></div>
  </div>

  <form class="big-search" method="get">
    <input name="q" value="<?=e($term)?>" minlength="2" maxlength="80" autocomplete="off" placeholder="Например: культура, школа, спорт">
    <button>Найти</button>
  </form>

  <?php if($searchError!==''):?>
    <div class="empty"><?=e($searchError)?></div>
  <?php elseif($articles): ?>
    <section class="listing-grid">
      <?php foreach($articles as $a): ?>
        <article class="list-card">
          <?php $thumbDynamicClass=!empty($a['cover_image']) ? csp_dynamic_class("background-image:url('".base_url($a['cover_image'])."');",'news-thumb') : ''; ?>
          <a class="list-thumb<?=empty($a['cover_image'])?' demo-road':''?> <?=e($thumbDynamicClass)?>" href="<?=e(article_url($a))?>"></a>
          <div class="list-card-body">
            <div class="article-label"><?=e($a['category_name'] ?: 'Новости')?></div>
            <h2><a href="<?=e(article_url($a))?>"><?=e($a['title'])?></a></h2>
            <p><?=e($a['excerpt'])?></p>
            <div class="article-meta"><span><?=e(ru_date($a['published_at'] ?: $a['created_at']))?></span><span>◉ <?=number_format((int)$a['views'],0,'.',' ')?></span></div>
          </div>
        </article>
      <?php endforeach; ?>
    </section>
  <?php elseif($term!==''): ?>
    <div class="empty">По вашему запросу ничего не найдено. Попробуйте изменить формулировку.</div>
  <?php else: ?>
    <div class="empty">Введите поисковый запрос выше.</div>
  <?php endif; ?>
</div>
<?php require __DIR__.'/partials/footer.php'; ?>
