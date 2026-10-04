<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_editor_permission('photos');

$error='';
$albumId=(int)($_GET['album_id']??0);
$editing=$albumId ? photo_album($albumId,false) : null;
if($albumId && !$editing) $albumId=0;

if($_SERVER['REQUEST_METHOD']==='POST'){
  verify_csrf();

  try{
    $action=(string)($_POST['action']??'save_album');

    if($action==='save_album'){
      $saveId=(int)($_POST['album_id']??0);
      $title=trim($_POST['title']??'');
      $description=sanitize_rich_text($_POST['description']??'');
      $albumDate=trim($_POST['album_date']??'') ?: date('Y-m-d');
      $status=in_array($_POST['status']??'published',['draft','published'],true)?$_POST['status']:'published';
      $sortOrder=max(-9999,min(9999,(int)($_POST['sort_order']??100)));

      if($title==='') throw new RuntimeException('Введите название фотоальбома.');

      if($saveId){
        $q=db()->prepare('UPDATE photo_albums SET title=?,description=?,album_date=?,status=?,sort_order=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
        $q->execute([$title,$description!==''?$description:null,$albumDate,$status,$sortOrder,$saveId]);
        $savedId=$saveId;
      }else{
        $q=db()->prepare('INSERT INTO photo_albums(title,description,album_date,status,sort_order) VALUES(?,?,?,?,?)');
        $q->execute([$title,$description!==''?$description:null,$albumDate,$status,$sortOrder]);
        $savedId=(int)db()->lastInsertId();
      }

      header('Location: '.base_url('admin/photo-gallery.php?album_id='.$savedId.'&saved=1'));
      exit;
    }

    if($action==='delete_album'){
      $deleteId=(int)($_POST['album_id']??0);
      $images=photo_album_images($deleteId);
      foreach($images as $image) safe_delete_gallery_image($image['image_path']??null);
      db()->prepare('DELETE FROM photo_albums WHERE id=?')->execute([$deleteId]);
      header('Location: '.base_url('admin/photo-gallery.php?deleted=1'));
      exit;
    }

    if($action==='upload_photos'){
      $targetAlbum=(int)($_POST['album_id']??0);
      if(!photo_album($targetAlbum,false)) throw new RuntimeException('Сначала выберите или создайте фотоальбом.');

      $files=$_FILES['photos']??null;
      if(!$files || !isset($files['name']) || !is_array($files['name'])) {
        throw new RuntimeException('Выберите фотографии для загрузки.');
      }

      $uploaded=0;
      $max=count($files['name']);
      for($i=0;$i<$max;$i++){
        if(($files['error'][$i]??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) continue;
        if($uploaded>=30) break;

        $single=[
          'name'=>$files['name'][$i]??'photo',
          'type'=>$files['type'][$i]??'',
          'tmp_name'=>$files['tmp_name'][$i]??'',
          'error'=>$files['error'][$i]??UPLOAD_ERR_NO_FILE,
          'size'=>$files['size'][$i]??0,
        ];
        $path=handle_cover_upload($single,null);
        if(!$path) continue;

        $q=db()->prepare('INSERT INTO photo_gallery_images(album_id,image_path,caption,sort_order) VALUES(?,?,NULL,?)');
        $q->execute([$targetAlbum,$path,100+$uploaded]);
        $uploaded++;
      }

      if($uploaded===0) throw new RuntimeException('Не удалось загрузить ни одной фотографии.');
      header('Location: '.base_url('admin/photo-gallery.php?album_id='.$targetAlbum.'&photos_added='.$uploaded));
      exit;
    }

    if($action==='update_photos'){
      $targetAlbum=(int)($_POST['album_id']??0);
      $captions=$_POST['caption']??[];
      $orders=$_POST['photo_order']??[];

      if(is_array($captions)){
        $q=db()->prepare('UPDATE photo_gallery_images SET caption=?,sort_order=? WHERE id=? AND album_id=?');
        foreach($captions as $photoId=>$caption){
          $photoId=(int)$photoId;
          $caption=trim((string)$caption);
          $order=max(-9999,min(9999,(int)($orders[$photoId]??100)));
          $q->execute([$caption!==''?$caption:null,$order,$photoId,$targetAlbum]);
        }
      }

      header('Location: '.base_url('admin/photo-gallery.php?album_id='.$targetAlbum.'&photos_saved=1'));
      exit;
    }

    if($action==='delete_photo'){
      $photoId=(int)($_POST['photo_id']??0);
      $q=db()->prepare('SELECT album_id,image_path FROM photo_gallery_images WHERE id=? LIMIT 1');
      $q->execute([$photoId]);
      $photo=$q->fetch();

      if($photo){
        safe_delete_gallery_image($photo['image_path']??null);
        db()->prepare('DELETE FROM photo_gallery_images WHERE id=?')->execute([$photoId]);
        $targetAlbum=(int)$photo['album_id'];
      }else{
        $targetAlbum=(int)($_POST['album_id']??0);
      }

      header('Location: '.base_url('admin/photo-gallery.php?album_id='.$targetAlbum.'&photo_deleted=1'));
      exit;
    }

    throw new RuntimeException('Неизвестное действие.');
  }catch(Throwable $e){
    $error=$e->getMessage();
  }
}

$albums=photo_albums(false);
$albumPager=admin_paginate_array($albums,8,'album_page');
$albums=$albumPager['items'];

$editing=$albumId ? photo_album($albumId,false) : null;
$photos=$editing ? photo_album_images((int)$editing['id']) : [];
$photoPager=admin_paginate_array($photos,12,'photo_page');
$photos=$photoPager['items'];

$adminTitle='Фотогалерея';
require __DIR__.'/_top.php';
?>

<?php if($error):?><div class="error"><?=e($error)?></div><?php endif;?>
<?php if(isset($_GET['saved'])):?><div class="ok">Фотоальбом сохранён.</div><?php endif;?>
<?php if(isset($_GET['deleted'])):?><div class="ok">Фотоальбом удалён.</div><?php endif;?>
<?php if(isset($_GET['photos_added'])):?><div class="ok">Добавлено фотографий: <?=e((string)(int)$_GET['photos_added'])?>.</div><?php endif;?>
<?php if(isset($_GET['photos_saved'])):?><div class="ok">Подписи и порядок фотографий сохранены.</div><?php endif;?>
<?php if(isset($_GET['photo_deleted'])):?><div class="ok">Фотография удалена.</div><?php endif;?>

<div class="gallery-admin-head">
  <div>
    <span class="editor-eyebrow">Медиа редакции</span>
    <h2>Фотогалерея</h2>
    <p>Создавайте фотоальбомы, а затем загружайте внутрь каждого альбома фотографии. Опубликованные альбомы автоматически появляются на главной и на странице «Фото».</p>
  </div>
  <?php if($editing):?><a class="editor-back" href="<?=e(base_url('admin/photo-gallery.php'))?>">＋ Новый альбом</a><?php endif;?>
</div>

<div class="gallery-admin-layout">
  <section class="editor-card gallery-album-editor">
    <div class="side-card-title">
      <span class="side-icon">▣</span>
      <div>
        <h3><?=$editing?'Редактировать альбом':'Создать фотоальбом'?></h3>
        <p>Основная информация альбома</p>
      </div>
    </div>

    <form method="post">
      <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
      <input type="hidden" name="action" value="save_album">
      <input type="hidden" name="album_id" value="<?=e((string)($editing['id']??0))?>">

      <label class="field-modern compact">
        <span>Название альбома</span>
        <input name="title" required maxlength="255" value="<?=e($editing['title']??'')?>" placeholder="Например: День района — 2026">
      </label>

      <label class="field-modern">
        <span>Описание</span>
        <textarea name="description" rows="5" data-rich-text placeholder="Кратко расскажите о событии"><?=e($editing['description']??'')?></textarea>
      </label>

      <div class="right-block-form-row">
        <label class="field-modern compact">
          <span>Дата альбома</span>
          <input type="date" name="album_date" value="<?=e($editing['album_date']??date('Y-m-d'))?>">
        </label>
        <label class="field-modern compact">
          <span>Статус</span>
          <select name="status">
            <option value="published" <?=($editing['status']??'published')==='published'?'selected':''?>>Опубликован</option>
            <option value="draft" <?=($editing['status']??'')==='draft'?'selected':''?>>Черновик</option>
          </select>
        </label>
      </div>

      <label class="field-modern compact">
        <span>Порядок</span>
        <input type="number" name="sort_order" min="-9999" max="9999" value="<?=e((string)($editing['sort_order']??100))?>">
      </label>

      <button class="primary wide" type="submit"><?=$editing?'Сохранить альбом':'Создать альбом'?></button>
    </form>
  </section>

  <section class="editor-card gallery-albums-card">
    <div class="card-head">
      <div>
        <h2>Фотоальбомы</h2>
        <p class="admin-intro">Первая фотография по порядку используется как обложка альбома.</p>
      </div>
    </div>

    <?php if($albums):?>
      <div class="gallery-albums-list">
        <?php foreach($albums as $album):?>
          <article class="gallery-album-admin-card">
            <?php $albumCoverClass=!empty($album['cover_image']) ? csp_dynamic_class("background-image:url('".base_url($album['cover_image'])."');",'admin-album-cover') : ''; ?>
            <a class="gallery-album-admin-cover <?=e($albumCoverClass)?>" href="<?=e(base_url('admin/photo-gallery.php?album_id='.$album['id']))?>"></a>
            <div class="gallery-album-admin-info">
              <span class="status <?=$album['status']==='published'?'green':'gray'?>"><?=$album['status']==='published'?'Опубликован':'Черновик'?></span>
              <h3><?=e($album['title'])?></h3>
              <div class="gallery-album-admin-meta">
                <span><?=e(ru_date($album['album_date']))?></span>
                <span><?=e((string)$album['photo_count'])?> фото</span>
              </div>
              <div class="gallery-album-admin-actions">
                <a class="edit-action" href="<?=e(base_url('admin/photo-gallery.php?album_id='.$album['id']))?>">Открыть альбом</a>
                <form method="post" data-confirm="Удалить альбом и все фотографии внутри?">
                  <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                  <input type="hidden" name="action" value="delete_album">
                  <input type="hidden" name="album_id" value="<?=$album['id']?>">
                  <button class="danger" type="submit">Удалить</button>
                </form>
              </div>
            </div>
          </article>
        <?php endforeach;?>
      </div>
      <?php render_admin_pagination('admin/photo-gallery.php',$albumPager['page'],$albumPager['total_pages'],[],'album_page','Страницы фотоальбомов'); ?>
    <?php else:?>
      <div class="gallery-empty-admin">
        <div><b>Фотоальбомов пока нет</b><p>Создайте первый альбом через форму слева.</p></div>
      </div>
    <?php endif;?>
  </section>
</div>

<?php if($editing):?>
<section class="editor-card gallery-photos-card">
  <div class="gallery-photos-head">
    <div>
      <span class="editor-eyebrow">Альбом</span>
      <h2><?=e($editing['title'])?></h2>
      <p>Загрузите фотографии. Первая по порядку будет обложкой альбома.</p>
    </div>
    <a class="editor-back" href="<?=e(base_url('gallery.php?album='.$editing['id']))?>" target="_blank">Посмотреть на сайте ↗</a>
  </div>

  <form method="post" enctype="multipart/form-data" class="gallery-upload-box">
    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
    <input type="hidden" name="action" value="upload_photos">
    <input type="hidden" name="album_id" value="<?=$editing['id']?>">
    <label class="field-modern compact">
      <span>Добавить фотографии</span>
      <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required>
      <small>Можно выбрать сразу несколько JPG, PNG или WEBP. За один раз — до 30 файлов.</small>
    </label>
    <button class="primary" type="submit">Загрузить фото</button>
  </form>

  <?php if($photos):?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
      <input type="hidden" name="action" value="update_photos">
      <input type="hidden" name="album_id" value="<?=$editing['id']?>">

      <div class="gallery-photo-admin-grid">
        <?php foreach($photos as $photo):?>
          <article class="gallery-photo-admin-item">
            <img src="<?=e(base_url($photo['image_path']))?>" alt="<?=e($photo['caption']??'')?>">
            <div class="gallery-photo-admin-fields">
              <input name="caption[<?=$photo['id']?>]" value="<?=e($photo['caption']??'')?>" maxlength="500" placeholder="Подпись к фотографии">
              <div class="gallery-photo-admin-row">
                <input type="number" name="photo_order[<?=$photo['id']?>]" value="<?=e((string)$photo['sort_order'])?>" min="-9999" max="9999" aria-label="Порядок фотографии">
                <button class="danger" type="submit" form="delete-photo-<?=$photo['id']?>">Удалить</button>
              </div>
            </div>
          </article>
        <?php endforeach;?>
      </div>

      <div class="gallery-photo-save-all"><button class="primary" type="submit">Сохранить подписи и порядок</button></div>
    </form>

    <?php render_admin_pagination('admin/photo-gallery.php',$photoPager['page'],$photoPager['total_pages'],['album_id'=>(int)$editing['id']],'photo_page','Страницы фотографий'); ?>

    <?php foreach($photos as $photo):?>
      <form id="delete-photo-<?=$photo['id']?>" method="post" data-confirm="Удалить эту фотографию?">
        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
        <input type="hidden" name="action" value="delete_photo">
        <input type="hidden" name="photo_id" value="<?=$photo['id']?>">
        <input type="hidden" name="album_id" value="<?=$editing['id']?>">
      </form>
    <?php endforeach;?>
  <?php else:?>
    <div class="gallery-empty-admin">
      <div><b>В альбоме пока нет фотографий</b><p>Выберите фотографии выше и загрузите их.</p></div>
    </div>
  <?php endif;?>
</section>
<?php endif;?>

<?php require __DIR__.'/_bottom.php'; ?>
