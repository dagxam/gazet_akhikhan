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
  require __DIR__.'/partials/header.php';
  echo '<div class="wrap page-shell"><div class="page-head"><div><span class="heading-kicker">404</span><h1>Материал не найден</h1><p>Возможно, публикация была перемещена или ещё не опубликована.</p></div><div class="page-head-mark"></div></div><div class="empty"><a href="'.e(base_url('news.php')).'">← Вернуться к новостям</a></div></div>';
  require __DIR__.'/partials/footer.php';
  exit;
}

db()->prepare('UPDATE articles SET views=views+1 WHERE id=?')->execute([$article['id']]);
$related = latest_articles(5, (int)$article['id']);

$pageTitle = $article['title'];
$pageDescription = $article['excerpt'];
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

      <?php if($article['excerpt']): ?><p class="article-lead"><?=e($article['excerpt'])?></p><?php endif; ?>
      <div class="article-content"><?=nl2br(e($article['content']))?></div>
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
    </aside>
  </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
