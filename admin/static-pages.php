<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$error='';
$id=(int)($_GET['id']??0);
$editing=$id ? static_page($id,false) : null;
if($id && !$editing) $id=0;

if($_SERVER['REQUEST_METHOD']==='POST'){
  verify_csrf();

  try{
    $action=(string)($_POST['action']??'save');

    if($action==='delete'){
      $deleteId=(int)($_POST['id']??0);
      $page=static_page($deleteId,false);
      if($page){
        if(!empty($page['menu_item_id'])) db()->prepare('DELETE FROM main_menu_items WHERE id=?')->execute([(int)$page['menu_item_id']]);
        db()->prepare("DELETE FROM main_menu_items WHERE url=?")->execute(['page-id:'.$deleteId]);
        safe_delete_static_page_cover($page['cover_image']??null);
        db()->prepare('DELETE FROM static_pages WHERE id=?')->execute([$deleteId]);
      }
      header('Location: '.base_url('admin/static-pages.php?deleted=1'));
      exit;
    }

    $saveId=(int)($_POST['id']??0);
    $current=$saveId ? static_page($saveId,false) : null;
    if($saveId && !$current) throw new RuntimeException('Страница не найдена.');

    $title=trim($_POST['title']??'');
    $slug=trim($_POST['slug']??'');
    $excerpt=sanitize_rich_text($_POST['excerpt']??'');
    $content=sanitize_rich_text($_POST['content']??'');
    $status=in_array($_POST['status']??'draft',['draft','published'],true)?$_POST['status']:'draft';
    $addToMenu=isset($_POST['add_to_menu']);
    $menuLabel=trim($_POST['menu_label']??'');
    $menuOrder=max(-9999,min(9999,(int)($_POST['menu_order']??100)));

    if($title==='') throw new RuntimeException('Введите название страницы.');
    if($content==='') throw new RuntimeException('Добавьте содержимое страницы.');
    $slug=$slug!=='' ? slugify($slug) : slugify($title);

    $oldCover=(string)($current['cover_image']??'');
    if(isset($_POST['remove_cover'])){
      safe_delete_static_page_cover($oldCover);
      $cover='';
    }else{
      $cover=handle_cover_upload($_FILES['cover']??[],$oldCover ?: null) ?? '';
      if($oldCover!=='' && $cover!==$oldCover) safe_delete_static_page_cover($oldCover);
    }

    $pdo=db();
    $pdo->beginTransaction();
    try{
      if($saveId){
        $q=$pdo->prepare('UPDATE static_pages SET title=?,slug=?,excerpt=?,content=?,cover_image=?,status=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
        $q->execute([$title,$slug,$excerpt!==''?$excerpt:null,$content,$cover!==''?$cover:null,$status,$saveId]);
        $pageId=$saveId;
      }else{
        $q=$pdo->prepare('INSERT INTO static_pages(title,slug,excerpt,content,cover_image,status) VALUES(?,?,?,?,?,?)');
        $q->execute([$title,$slug,$excerpt!==''?$excerpt:null,$content,$cover!==''?$cover:null,$status]);
        $pageId=(int)$pdo->lastInsertId();
      }

      sync_static_page_menu($pageId,$title,$slug,$addToMenu,$menuLabel,$menuOrder);
      $pdo->commit();
    }catch(Throwable $e){
      if($pdo->inTransaction()) $pdo->rollBack();
      if(str_contains(strtolower($e->getMessage()),'unique') || str_contains(strtolower($e->getMessage()),'duplicate')){
        throw new RuntimeException('Страница с таким URL уже существует. Измените адрес страницы.');
      }
      throw $e;
    }

    header('Location: '.base_url('admin/static-pages.php?id='.$pageId.'&saved=1'));
    exit;
  }catch(Throwable $e){
    $error=$e->getMessage();
  }
}

$editing=$id ? static_page($id,false) : null;
$menuItem=null;
if($editing && !empty($editing['menu_item_id'])){
  $q=db()->prepare('SELECT * FROM main_menu_items WHERE id=? LIMIT 1');
  $q->execute([(int)$editing['menu_item_id']]);
  $menuItem=$q->fetch() ?: null;
}
$pages=static_pages(false);
$staticPagesPager=admin_paginate_array($pages,10,'page');
$pages=$staticPagesPager['items'];

$adminTitle=$editing?'Редактирование страницы':'Статичные страницы';
require __DIR__.'/_top.php';

$currentTitle=$editing['title']??'';
$currentSlug=$editing['slug']??'';
$currentExcerpt=$editing['excerpt']??'';
$currentContent=$editing['content']??'';
$currentStatus=$editing['status']??'draft';
$addToMenu=$menuItem!==null;
$menuLabel=$menuItem['label']??'';
$menuOrder=$menuItem['sort_order']??100;
?>

<?php if($error):?><div class="error"><?=e($error)?></div><?php endif;?>
<?php if(isset($_GET['saved'])):?><div class="ok">Страница сохранена. Настройки меню синхронизированы.</div><?php endif;?>
<?php if(isset($_GET['deleted'])):?><div class="ok">Страница удалена.</div><?php endif;?>

<div class="static-pages-head">
  <div>
    <span class="editor-eyebrow">Структура сайта</span>
    <h2><?=$editing?'Редактирование страницы':'Статичные страницы'?></h2>
    <p>Создавайте постоянные страницы сайта: о редакции, контакты, правила, проекты и другие материалы. При необходимости страницу можно сразу добавить в главное меню.</p>
  </div>
  <?php if($editing):?><a class="editor-back" href="<?=e(base_url('admin/static-pages.php'))?>">＋ Новая страница</a><?php endif;?>
</div>

<div class="static-pages-layout">
  <form class="editor-form editorial-editor static-page-editor" method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?=e((string)($editing['id']??0))?>">

    <div class="editor-workspace">
      <div class="editor-main-column">
        <section class="editor-card editor-card-main">
          <div class="editor-section-head">
            <div><span class="section-number">01</span><h3>Основное</h3></div>
            <span class="section-help">Название и краткое описание</span>
          </div>

          <label class="field-modern field-title">
            <span>Название страницы</span>
            <input name="title" value="<?=e($currentTitle)?>" required maxlength="220" placeholder="Например: О редакции" data-title-input>
            <small><b data-title-count><?=function_exists('mb_strlen')?mb_strlen($currentTitle,'UTF-8'):strlen($currentTitle)?></b>/220</small>
          </label>

          <label class="field-modern">
            <span>Краткое описание</span>
            <textarea name="excerpt" rows="3" data-rich-text placeholder="Короткое описание для страницы и поисковых систем" data-excerpt-input><?=e($currentExcerpt)?></textarea>
            <small><b data-excerpt-count><?=function_exists('mb_strlen')?mb_strlen($currentExcerpt,'UTF-8'):strlen($currentExcerpt)?></b>/500</small>
          </label>
        </section>

        <section class="editor-card editor-card-content">
          <div class="editor-section-head">
            <div><span class="section-number">02</span><h3>Содержимое страницы</h3></div>
            <span class="section-help">Основной текст</span>
          </div>
          <textarea class="content-editor modern-content-editor" name="content" data-rich-text rows="24" placeholder="Введите текст статичной страницы..."><?=e($currentContent)?></textarea>
        </section>

        <section class="editor-card editor-card-link">
          <div class="editor-section-head">
            <div><span class="section-number">03</span><h3>Адрес страницы</h3></div>
            <span class="section-help">Можно оставить пустым</span>
          </div>
          <label class="field-modern">
            <span>URL-адрес</span>
            <div class="url-field"><span><?=e(parse_url(base_url(),PHP_URL_HOST) ?: 'akhikhan.ru')?>/page/</span><input name="slug" value="<?=e($currentSlug)?>" placeholder="создастся автоматически"></div>
          </label>
        </section>
      </div>

      <aside class="editor-side-column">
        <section class="editor-card editor-publish-card">
          <div class="side-card-title"><span class="side-icon">✓</span><div><h3>Публикация</h3><p>Статус страницы</p></div></div>
          <label class="field-modern compact">
            <span>Статус</span>
            <select name="status">
              <option value="draft" <?=$currentStatus==='draft'?'selected':''?>>Черновик</option>
              <option value="published" <?=$currentStatus==='published'?'selected':''?>>Опубликовано</option>
            </select>
          </label>
          <button class="primary wide editor-save-main" type="submit"><span>Сохранить страницу</span><b>→</b></button>
          <?php if($editing && $currentStatus==='published'):?><a class="editor-public-link" href="<?=e(static_page_url($editing))?>" target="_blank">Открыть на сайте ↗</a><?php endif;?>
        </section>

        <section class="editor-card static-page-menu-card">
          <div class="side-card-title"><span class="side-icon">☰</span><div><h3>Главное меню</h3><p>Добавлять страницу или нет</p></div></div>
          <label class="featured-switch">
            <input type="checkbox" name="add_to_menu" value="1" <?=$addToMenu?'checked':''?> data-static-menu-toggle>
            <span class="switch-ui"></span>
            <span><b>Добавить в главное меню</b><small>Черновик будет скрыт до публикации страницы</small></span>
          </label>
          <div class="static-page-menu-fields" data-static-menu-fields <?=$addToMenu?'':'hidden'?>>
            <label class="field-modern compact">
              <span>Название в меню</span>
              <input name="menu_label" maxlength="120" value="<?=e($menuLabel)?>" placeholder="Если пусто — название страницы">
            </label>
            <label class="field-modern compact">
              <span>Порядок</span>
              <input type="number" name="menu_order" min="-9999" max="9999" value="<?=e((string)$menuOrder)?>">
            </label>
          </div>
        </section>

        <section class="editor-card editor-cover-card">
          <div class="side-card-title"><span class="side-icon">▧</span><div><h3>Обложка</h3><p>Необязательное изображение</p></div></div>
          <label class="cover-dropzone" data-cover-zone>
            <input type="file" name="cover" accept="image/jpeg,image/png,image/webp" data-cover-input>
            <span class="cover-drop-icon">＋</span>
            <b><?=!empty($editing['cover_image'])?'Заменить изображение':'Добавить изображение'?></b>
            <small>JPG, PNG или WEBP</small>
          </label>
          <div class="cover-preview-shell <?=empty($editing['cover_image'])?'is-empty':''?>" data-cover-preview-shell>
            <?php if(!empty($editing['cover_image'])):?><img class="cover-preview" src="<?=e(base_url($editing['cover_image']))?>" alt="" data-cover-preview><?php else:?><img class="cover-preview" src="" alt="" data-cover-preview hidden><?php endif;?>
          </div>
          <?php if($editing && !empty($editing['cover_image'])):?><label class="menu-check"><input type="checkbox" name="remove_cover" value="1"><span>Удалить обложку</span></label><?php endif;?>
        </section>
      </aside>
    </div>
  </form>

  <section class="editor-card static-pages-library">
    <div class="card-head">
      <div>
        <h2>Созданные страницы</h2>
        <p class="admin-intro">Постоянные страницы сайта и их состояние.</p>
      </div>
    </div>

    <?php if($pages):?>
      <div class="static-pages-list">
        <?php foreach($pages as $page):?>
          <article class="static-page-admin-row">
            <div class="static-page-admin-state"><i class="fa-regular fa-file-lines"></i></div>
            <div class="static-page-admin-copy">
              <div><span class="status <?=$page['status']==='published'?'green':'gray'?>"><?=$page['status']==='published'?'Опубликована':'Черновик'?></span><?php if(!empty($page['menu_item_id'])):?><span class="static-page-menu-badge">В меню</span><?php endif;?></div>
              <strong><?=e($page['title'])?></strong>
              <small>/page/<?=e($page['slug'])?></small>
            </div>
            <div class="static-page-admin-actions">
              <a class="edit-action" href="<?=e(base_url('admin/static-pages.php?id='.$page['id']))?>">Редактировать</a>
              <?php if($page['status']==='published'):?><a class="edit-action" href="<?=e(static_page_url($page))?>" target="_blank">Открыть ↗</a><?php endif;?>
              <form method="post" data-confirm="Удалить эту страницу? Связанный пункт меню тоже будет удалён.">
                <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?=$page['id']?>">
                <button class="danger" type="submit">Удалить</button>
              </form>
            </div>
          </article>
        <?php endforeach;?>
      </div>
      <?php render_admin_pagination('admin/static-pages.php',$staticPagesPager['page'],$staticPagesPager['total_pages'],[],'page','Страницы статичных страниц'); ?>
    <?php else:?>
      <div class="menu-admin-empty"><div><b>Статичных страниц пока нет</b><p>Создайте первую страницу в редакторе выше.</p></div></div>
    <?php endif;?>
  </section>
</div>

<script nonce="<?=e(csp_nonce())?>">
(function(){
  const toggle=document.querySelector('[data-static-menu-toggle]');
  const fields=document.querySelector('[data-static-menu-fields]');
  if(!toggle||!fields) return;
  const sync=()=>fields.toggleAttribute('hidden',!toggle.checked);
  toggle.addEventListener('change',sync);
  sync();
})();
</script>

<?php require __DIR__.'/_bottom.php'; ?>
