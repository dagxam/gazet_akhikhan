<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$adminTitle='Обзор';

$stats=[
  ['key'=>'published','value'=>(int)db()->query("SELECT COUNT(*) FROM articles WHERE status='published'")->fetchColumn(),'label'=>'Опубликовано','icon'=>'fa-solid fa-newspaper','href'=>'admin/articles.php'],
  ['key'=>'drafts','value'=>(int)db()->query("SELECT COUNT(*) FROM articles WHERE status='draft'")->fetchColumn(),'label'=>'Черновики','icon'=>'fa-regular fa-file','href'=>'admin/articles.php'],
  ['key'=>'views','value'=>(int)db()->query("SELECT COALESCE(SUM(views),0) FROM articles")->fetchColumn(),'label'=>'Просмотры новостей','icon'=>'fa-regular fa-eye','href'=>'admin/articles.php'],
  ['key'=>'categories','value'=>(int)db()->query("SELECT COUNT(*) FROM categories")->fetchColumn(),'label'=>'Рубрики','icon'=>'fa-solid fa-layer-group','href'=>'admin/categories.php'],
  ['key'=>'newspapers','value'=>(int)db()->query("SELECT COUNT(*) FROM newspapers")->fetchColumn(),'label'=>'Выпуски газеты','icon'=>'fa-solid fa-book-open','href'=>'admin/newspapers.php'],
  ['key'=>'documents','value'=>(int)db()->query("SELECT COUNT(*) FROM documents")->fetchColumn(),'label'=>'Документы','icon'=>'fa-regular fa-file-lines','href'=>'admin/documents.php'],
  ['key'=>'albums','value'=>(int)db()->query("SELECT COUNT(*) FROM photo_albums")->fetchColumn(),'label'=>'Фотоальбомы','icon'=>'fa-regular fa-images','href'=>'admin/photo-gallery.php'],
  ['key'=>'photos','value'=>(int)db()->query("SELECT COUNT(*) FROM photo_gallery_images")->fetchColumn(),'label'=>'Фотографии','icon'=>'fa-regular fa-image','href'=>'admin/photo-gallery.php'],
  ['key'=>'videos','value'=>(int)db()->query("SELECT COUNT(*) FROM video_gallery")->fetchColumn(),'label'=>'Видео','icon'=>'fa-solid fa-video','href'=>'admin/video-gallery.php'],
  ['key'=>'pages','value'=>(int)db()->query("SELECT COUNT(*) FROM static_pages")->fetchColumn(),'label'=>'Статичные страницы','icon'=>'fa-regular fa-file-lines','href'=>'admin/static-pages.php'],
  ['key'=>'menu','value'=>(int)db()->query("SELECT COUNT(*) FROM main_menu_items WHERE is_active=1")->fetchColumn(),'label'=>'Пунктов меню','icon'=>'fa-solid fa-bars','href'=>'admin/main-menu.php'],
  ['key'=>'social','value'=>(int)db()->query("SELECT COUNT(*) FROM social_links WHERE is_active=1")->fetchColumn(),'label'=>'Соцсети','icon'=>'fa-solid fa-share-nodes','href'=>'admin/social-links.php'],
  ['key'=>'home_blocks','value'=>(int)db()->query("SELECT COUNT(*) FROM homepage_right_blocks WHERE area='home' AND is_active=1")->fetchColumn(),'label'=>'Блоки на главной','icon'=>'fa-solid fa-table-columns','href'=>'admin/right-block.php?area=home'],
  ['key'=>'page_blocks','value'=>(int)db()->query("SELECT COUNT(*) FROM homepage_right_blocks WHERE area='pages' AND is_active=1")->fetchColumn(),'label'=>'Блоки на страницах','icon'=>'fa-regular fa-rectangle-list','href'=>'admin/right-block.php?area=pages'],
];

$recent=db()->query("SELECT id,title,status,published_at,created_at,updated_at,views FROM articles ORDER BY updated_at DESC LIMIT 8")->fetchAll();
$latestVideos=db()->query("SELECT * FROM video_gallery ORDER BY video_date DESC,updated_at DESC,id DESC LIMIT 6")->fetchAll();

require __DIR__.'/_top.php';
?>

<div class="dashboard-report-head">
  <div>
    <span class="editor-eyebrow">Сводка сайта</span>
    <h2>Обзор публикаций и разделов</h2>
    <p>Основные данные по наполнению сайта на текущий момент.</p>
  </div>
  <div class="dashboard-report-actions">
    <a class="secondary" href="<?=e(base_url('admin/static-pages.php'))?>">Статичная страница</a>
    <a class="primary" href="<?=e(base_url('admin/article-edit.php'))?>">+ Добавить новость</a>
  </div>
</div>

<section class="dashboard-stats-grid">
  <?php foreach($stats as $stat):?>
    <a class="dashboard-stat-card dashboard-stat-<?=e($stat['key'])?>" href="<?=e(base_url($stat['href']))?>">
      <span class="dashboard-stat-icon"><i class="<?=e($stat['icon'])?>" aria-hidden="true"></i></span>
      <span class="dashboard-stat-copy">
        <b><?=number_format((int)$stat['value'],0,'.',' ')?></b>
        <small><?=e($stat['label'])?></small>
      </span>
      <i class="fa-solid fa-chevron-right dashboard-stat-arrow" aria-hidden="true"></i>
    </a>
  <?php endforeach;?>
</section>

<div class="dashboard-main-grid">
  <section class="admin-card dashboard-recent-card">
    <div class="card-head dashboard-card-head">
      <div>
        <span class="editor-eyebrow">Новости</span>
        <h2>Последние материалы</h2>
        <p class="admin-intro">Последние изменённые новости и их состояние.</p>
      </div>
      <a class="edit-action" href="<?=e(base_url('admin/articles.php'))?>">Все новости →</a>
    </div>

    <?php if($recent):?>
      <div class="table-scroll">
        <table class="dashboard-recent-table">
          <thead><tr><th>Материал</th><th>Дата</th><th>Статус</th><th>Просмотры</th></tr></thead>
          <tbody>
            <?php foreach($recent as $r):
              $materialDate=$r['published_at'] ?: $r['created_at'];
            ?>
              <tr>
                <td><a class="news-title-link" href="<?=e(base_url('admin/article-edit.php?id='.$r['id']))?>"><strong><?=e($r['title'])?></strong></a></td>
                <td><?=e(ru_date($materialDate))?></td>
                <td><span class="status <?=$r['status']==='published'?'green':'gray'?>"><?=$r['status']==='published'?'Опубликовано':'Черновик'?></span></td>
                <td><?=number_format((int)$r['views'],0,'.',' ')?></td>
              </tr>
            <?php endforeach;?>
          </tbody>
        </table>
      </div>
    <?php else:?>
      <div class="dashboard-empty">Новостей пока нет.</div>
    <?php endif;?>
  </section>

  <section class="admin-card dashboard-video-card">
    <div class="card-head dashboard-card-head">
      <div>
        <span class="editor-eyebrow">Видеогалерея</span>
        <h2>Последние видео</h2>
        <p class="admin-intro">Шесть последних материалов, добавленных в видеогалерею.</p>
      </div>
      <a class="edit-action" href="<?=e(base_url('admin/video-gallery.php'))?>">Видеогалерея →</a>
    </div>

    <?php if($latestVideos):?>
      <div class="dashboard-video-grid">
        <?php foreach($latestVideos as $video):?>
          <a class="dashboard-video-item" href="<?=e(base_url('admin/video-gallery.php?id='.$video['id']))?>">
            <span class="dashboard-video-preview">
              <?php if(!empty($video['cover_image'])):?>
                <img src="<?=e(base_url($video['cover_image']))?>" alt="<?=e($video['title'])?>">
              <?php else:?>
                <span class="dashboard-video-placeholder"><i class="fa-solid fa-video"></i></span>
              <?php endif;?>
              <span class="dashboard-video-play"><i class="fa-solid fa-play"></i></span>
              <span class="dashboard-video-provider"><?=e(video_provider_label($video['provider']))?></span>
            </span>
            <span class="dashboard-video-copy">
              <small><?=e(ru_date($video['video_date']))?></small>
              <strong><?=e($video['title'])?></strong>
              <span class="status <?=$video['status']==='published'?'green':'gray'?>"><?=$video['status']==='published'?'Опубликовано':'Черновик'?></span>
            </span>
          </a>
        <?php endforeach;?>
      </div>
    <?php else:?>
      <div class="dashboard-video-empty">
        <i class="fa-solid fa-video"></i>
        <b>Видео пока не добавлены</b>
        <a href="<?=e(base_url('admin/video-gallery.php'))?>">Добавить первое видео →</a>
      </div>
    <?php endif;?>
  </section>
</div>

<?php require __DIR__.'/_bottom.php'; ?>
