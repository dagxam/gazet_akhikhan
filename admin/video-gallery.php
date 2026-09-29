<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$error='';
$id=(int)($_GET['id']??0);
$editing=$id ? video_gallery_item($id,false) : null;
if($id && !$editing) $id=0;

if($_SERVER['REQUEST_METHOD']==='POST'){
  verify_csrf();

  try{
    $action=(string)($_POST['action']??'save');

    if($action==='delete'){
      $deleteId=(int)($_POST['id']??0);
      $item=video_gallery_item($deleteId,false);
      if($item){
        safe_delete_video_upload($item['video_file']??null);
        safe_delete_video_cover($item['cover_image']??null);
        db()->prepare('DELETE FROM video_gallery WHERE id=?')->execute([$deleteId]);
      }
      header('Location: '.base_url('admin/video-gallery.php?deleted=1'));
      exit;
    }

    $saveId=(int)($_POST['id']??0);
    $old=$saveId ? video_gallery_item($saveId,false) : null;

    $title=trim($_POST['title']??'');
    $description=sanitize_rich_text($_POST['description']??'');
    $videoDate=trim($_POST['video_date']??'') ?: date('Y-m-d');
    $sourceType=in_array($_POST['source_type']??'external',['external','local'],true)?$_POST['source_type']:'external';
    $status=in_array($_POST['status']??'published',['draft','published'],true)?$_POST['status']:'published';
    $sortOrder=max(-9999,min(9999,(int)($_POST['sort_order']??100)));

    if($title==='') throw new RuntimeException('Введите название видео.');

    $oldCover=$old['cover_image']??null;
    $cover=$oldCover;
    if(isset($_POST['remove_cover'])){
      safe_delete_video_cover($oldCover);
      $cover=null;
    }elseif(isset($_FILES['cover']) && ($_FILES['cover']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){
      $cover=handle_cover_upload($_FILES['cover'],null);
      if($oldCover && $cover!==$oldCover) safe_delete_video_cover($oldCover);
    }

    $provider=null;
    $sourceUrl=null;
    $videoFile=$old['video_file']??null;

    if($sourceType==='external'){
      [$provider,$sourceUrl]=normalize_video_source_url((string)($_POST['source_url']??''));

      if($old && !empty($old['video_file'])){
        safe_delete_video_upload($old['video_file']);
      }
      $videoFile=null;
    }else{
      $provider='local';
      $sourceUrl=null;

      $keepOld=($old && ($old['source_type']??'')==='local') ? ($old['video_file']??null) : null;
      $videoFile=handle_video_upload($_FILES['video_file']??[],$keepOld);

      if(!$videoFile){
        throw new RuntimeException('Выберите видеофайл для загрузки на сервер.');
      }
    }

    if($saveId){
      $q=db()->prepare('UPDATE video_gallery SET title=?,description=?,video_date=?,source_type=?,provider=?,source_url=?,video_file=?,cover_image=?,status=?,sort_order=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
      $q->execute([
        $title,
        $description!==''?$description:null,
        $videoDate,
        $sourceType,
        $provider,
        $sourceUrl,
        $videoFile,
        $cover,
        $status,
        $sortOrder,
        $saveId
      ]);
      $savedId=$saveId;
    }else{
      $q=db()->prepare('INSERT INTO video_gallery(title,description,video_date,source_type,provider,source_url,video_file,cover_image,status,sort_order) VALUES(?,?,?,?,?,?,?,?,?,?)');
      $q->execute([
        $title,
        $description!==''?$description:null,
        $videoDate,
        $sourceType,
        $provider,
        $sourceUrl,
        $videoFile,
        $cover,
        $status,
        $sortOrder
      ]);
      $savedId=(int)db()->lastInsertId();
    }

    header('Location: '.base_url('admin/video-gallery.php?id='.$savedId.'&saved=1'));
    exit;
  }catch(Throwable $e){
    $error=$e->getMessage();
  }
}

$editing=$id ? video_gallery_item($id,false) : null;
$videos=video_gallery_items(false);
$adminTitle='Видеогалерея';
require __DIR__.'/_top.php';
?>

<?php if($error):?><div class="error"><?=e($error)?></div><?php endif;?>
<?php if(isset($_GET['saved'])):?><div class="ok">Видео сохранено и готово к публикации.</div><?php endif;?>
<?php if(isset($_GET['deleted'])):?><div class="ok">Видео удалено.</div><?php endif;?>

<div class="video-admin-head">
  <div>
    <span class="editor-eyebrow">Медиа редакции</span>
    <h2>Видеогалерея</h2>
    <p>Добавляйте видео по ссылкам VK, Rutube и Одноклассников или загружайте видеофайл прямо на сервер. Опубликованные материалы автоматически появляются на странице «Видео».</p>
  </div>
  <?php if($editing):?><a class="editor-back" href="<?=e(base_url('admin/video-gallery.php'))?>">＋ Добавить видео</a><?php endif;?>
</div>

<div class="video-admin-layout">
  <section class="editor-card video-editor-card">
    <div class="side-card-title">
      <span class="side-icon"><i class="fa-solid fa-video"></i></span>
      <div>
        <h3><?=$editing?'Редактировать видео':'Новое видео'?></h3>
        <p>Ссылка или файл на сервере</p>
      </div>
    </div>

    <form method="post" enctype="multipart/form-data" data-video-admin-form>
      <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?=e((string)($editing['id']??0))?>">

      <label class="field-modern compact">
        <span>Название видео</span>
        <input name="title" required maxlength="255" value="<?=e($editing['title']??'')?>" placeholder="Например: Праздник в Унцукульском районе">
      </label>

      <label class="field-modern">
        <span>Описание</span>
        <textarea name="description" rows="4" data-rich-text placeholder="Краткое описание видеоматериала"><?=e($editing['description']??'')?></textarea>
      </label>

      <div class="video-source-switch">
        <label class="video-source-choice">
          <input type="radio" name="source_type" value="external" <?=($editing['source_type']??'external')==='external'?'checked':''?> data-video-source-choice>
          <i class="fa-solid fa-link"></i>
          <span>Ссылка на видео</span>
        </label>
        <label class="video-source-choice">
          <input type="radio" name="source_type" value="local" <?=($editing['source_type']??'')==='local'?'checked':''?> data-video-source-choice>
          <i class="fa-solid fa-upload"></i>
          <span>Файл на сервере</span>
        </label>
      </div>

      <div class="video-source-fields" data-video-external>
        <label class="field-modern compact">
          <span>Ссылка VK / Rutube / Одноклассники</span>
          <input type="url" name="source_url" maxlength="1000" value="<?=e($editing['source_url']??'')?>" placeholder="https://rutube.ru/video/...">
        </label>
        <p class="video-source-help">Поддерживаются только VK, Rutube и Одноклассники. Обычную ссылку система преобразует во встроенный проигрыватель.</p>
      </div>

      <div class="video-source-fields" data-video-local>
        <label class="field-modern compact">
          <span>Видеофайл</span>
          <input type="file" name="video_file" accept="video/mp4,video/webm,video/ogg,video/quicktime,.m4v">
          <small>MP4, WEBM, OGV/OGG, MOV или M4V. До 300 МБ, если лимит хостинга не ниже.</small>
        </label>
        <?php if($editing && !empty($editing['video_file'])):?><p class="video-source-help">Сейчас загружен файл: <?=e(basename($editing['video_file']))?>. Если новый файл не выбирать, текущий останется.</p><?php endif;?>
      </div>

      <label class="field-modern compact">
        <span>Обложка видео</span>
        <input type="file" name="cover" accept="image/jpeg,image/png,image/webp">
        <small>Необязательно. JPG, PNG или WEBP до 8 МБ.</small>
      </label>

      <?php if($editing && !empty($editing['cover_image'])):?>
        <div class="video-cover-current"><img src="<?=e(base_url($editing['cover_image']))?>" alt=""></div>
        <label class="video-remove-check"><input type="checkbox" name="remove_cover" value="1"> Удалить текущую обложку</label>
      <?php endif;?>

      <div class="right-block-form-row">
        <label class="field-modern compact">
          <span>Дата видео</span>
          <input type="date" name="video_date" value="<?=e($editing['video_date']??date('Y-m-d'))?>">
        </label>
        <label class="field-modern compact">
          <span>Статус</span>
          <select name="status">
            <option value="published" <?=($editing['status']??'published')==='published'?'selected':''?>>Опубликовано</option>
            <option value="draft" <?=($editing['status']??'')==='draft'?'selected':''?>>Черновик</option>
          </select>
        </label>
      </div>

      <label class="field-modern compact">
        <span>Порядок</span>
        <input type="number" name="sort_order" min="-9999" max="9999" value="<?=e((string)($editing['sort_order']??100))?>">
      </label>

      <button class="primary wide" type="submit"><?=$editing?'Сохранить видео':'Добавить видео'?></button>
    </form>
  </section>

  <section class="editor-card video-library-card">
    <div class="card-head">
      <div>
        <h2>Видео</h2>
        <p class="admin-intro">Все материалы видеогалереи. Черновики на сайте не показываются.</p>
      </div>
    </div>

    <?php if($videos):?>
      <div class="video-admin-list">
        <?php foreach($videos as $video):?>
          <article class="video-admin-item <?=$video['status']==='published'?'':'is-disabled'?>">
            <div class="video-admin-preview">
              <?php if(!empty($video['cover_image'])):?><img src="<?=e(base_url($video['cover_image']))?>" alt="<?=e($video['title'])?>"><?php endif;?>
              <span class="video-admin-play"><i class="fa-solid fa-play"></i></span>
              <span class="video-admin-provider"><?=e(video_provider_label($video['provider']))?></span>
            </div>
            <div class="video-admin-info">
              <span class="status <?=$video['status']==='published'?'green':'gray'?>"><?=$video['status']==='published'?'Опубликовано':'Черновик'?></span>
              <h3><?=e($video['title'])?></h3>
              <div class="video-admin-meta">
                <span><?=e(ru_date($video['video_date']))?></span>
                <span><?=e($video['source_type']==='local'?'Файл на сервере':'Внешняя ссылка')?></span>
              </div>
              <div class="video-admin-actions">
                <a class="edit-action" href="<?=e(base_url('admin/video-gallery.php?id='.$video['id']))?>">Редактировать</a>
                <?php if($video['status']==='published'):?><a class="edit-action" href="<?=e(base_url('videos.php?id='.$video['id']))?>" target="_blank">Смотреть ↗</a><?php endif;?>
                <form method="post" onsubmit="return confirm('Удалить это видео? Загруженный видеофайл тоже будет удалён с сервера.')">
                  <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?=$video['id']?>">
                  <button class="danger" type="submit">Удалить</button>
                </form>
              </div>
            </div>
          </article>
        <?php endforeach;?>
      </div>
    <?php else:?>
      <div class="video-admin-empty">
        <div>
          <b>Видеогалерея пока пустая</b>
          <p>Добавьте первое видео по ссылке VK, Rutube, Одноклассников или загрузите файл на сервер.</p>
        </div>
      </div>
    <?php endif;?>
  </section>
</div>

<script>
(function(){
  const form=document.querySelector('[data-video-admin-form]');
  if(!form) return;
  const radios=[...form.querySelectorAll('[data-video-source-choice]')];
  const external=form.querySelector('[data-video-external]');
  const local=form.querySelector('[data-video-local]');

  function sync(){
    const type=form.querySelector('input[name="source_type"]:checked')?.value||'external';
    external.hidden=type!=='external';
    local.hidden=type!=='local';
    const url=external.querySelector('input[name="source_url"]');
    const file=local.querySelector('input[name="video_file"]');
    if(url) url.required=type==='external';
    if(file) file.required=type==='local' && <?=($editing && !empty($editing['video_file']) && ($editing['source_type']??'')==='local')?'false':'true'?>;
  }

  radios.forEach(r=>r.addEventListener('change',sync));
  sync();
})();
</script>

<?php require __DIR__.'/_bottom.php'; ?>
