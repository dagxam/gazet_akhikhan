<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$id=(int)($_GET['id']??0);
$article=null;

if($id){
  $q=db()->prepare('SELECT * FROM articles WHERE id=?');
  $q->execute([$id]);
  $article=$q->fetch();
  if(!$article) exit('Материал не найден');
}

$error='';

if($_SERVER['REQUEST_METHOD']==='POST'){
  verify_csrf();
  try{
    $title=trim($_POST['title']??'');
    if(!$title) throw new RuntimeException('Введите заголовок.');

    $slug=trim($_POST['slug']??'') ?: slugify($title);

    $newCategoryName=trim($_POST['new_category_name']??'');
    $newCategoryDescription=trim($_POST['new_category_description']??'');
    if($newCategoryName!==''){
      $category=find_or_create_category($newCategoryName,$newCategoryDescription);
    } else {
      $category=(int)($_POST['category_id']??0) ?: null;
    }

    $excerpt=trim($_POST['excerpt']??'');
    $content=trim($_POST['content']??'');
    $status=in_array($_POST['status']??'draft',['draft','published'],true)?$_POST['status']:'draft';
    $featured=isset($_POST['is_featured'])?1:0;
    $publishedAt=trim($_POST['published_at']??'');
    $publishedAt=$publishedAt?str_replace('T',' ',$publishedAt).(strlen($publishedAt)===16?':00':''):null;
    $cover=handle_cover_upload($_FILES['cover']??[], $article['cover_image']??null);

    if($featured){
      db()->exec('UPDATE articles SET is_featured=0');
    }

    if($id){
      $q=db()->prepare('UPDATE articles SET category_id=?,title=?,slug=?,excerpt=?,content=?,cover_image=?,status=?,is_featured=?,published_at=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
      $q->execute([$category,$title,$slug,$excerpt,$content,$cover,$status,$featured,$publishedAt,$id]);
    } else {
      $q=db()->prepare('INSERT INTO articles(category_id,author_id,title,slug,excerpt,content,cover_image,status,is_featured,published_at) VALUES(?,?,?,?,?,?,?,?,?,?)');
      $q->execute([$category,admin_user()['id'],$title,$slug,$excerpt,$content,$cover,$status,$featured,$publishedAt]);
      $id=(int)db()->lastInsertId();
    }

    header('Location: '.base_url('admin/article-edit.php?id='.$id.'&saved=1'));
    exit;
  }catch(Throwable $e){
    $error=$e->getMessage();
  }
}

if($id){
  $q=db()->prepare('SELECT * FROM articles WHERE id=?');
  $q->execute([$id]);
  $article=$q->fetch();
}

$cats=categories();
$adminTitle=$id?'Редактирование новости':'Новая новость';
require __DIR__.'/_top.php';

$currentStatus=$article['status']??'draft';
$currentCategory=(int)($article['category_id']??0);
$currentTitle=$article['title']??'';
$currentExcerpt=$article['excerpt']??'';
$currentContent=$article['content']??'';
$currentSlug=$article['slug']??'';
$currentPublished=!empty($article['published_at'])?date('Y-m-d\\TH:i',strtotime($article['published_at'])):'';
?>
<?php if($error):?><div class="error"><?=e($error)?></div><?php endif;?>
<?php if(isset($_GET['saved'])):?><div class="ok editor-notice">Изменения сохранены.</div><?php endif;?>

<form class="editor-form editorial-editor" method="post" enctype="multipart/form-data" data-editor-form>
<input type="hidden" name="csrf" value="<?=e(csrf_token())?>">

<div class="editor-page-head">
  <div>
    <span class="editor-eyebrow"><?=$id?'Редактирование материала':'Новый материал'?></span>
    <h2><?=$id?'Редактирование новости':'Создание новости'?></h2>
    <p>Подготовьте материал, выберите рубрику и настройте публикацию.</p>
  </div>
  <div class="editor-page-actions">
    <a class="editor-back" href="<?=e(base_url('admin/articles.php'))?>">← К списку</a>
    <button class="primary editor-save-top" type="submit">Сохранить</button>
  </div>
</div>

<div class="editor-workspace">
  <div class="editor-main-column">
    <section class="editor-card editor-card-main">
      <div class="editor-section-head">
        <div><span class="section-number">01</span><h3>Основное</h3></div>
        <span class="section-help">То, что увидит читатель</span>
      </div>

      <label class="field-modern field-title">
        <span>Заголовок новости</span>
        <input name="title" value="<?=e($currentTitle)?>" required maxlength="220" placeholder="Введите ясный и информативный заголовок" data-title-input>
        <small><b data-title-count><?=function_exists('mb_strlen')?mb_strlen($currentTitle,'UTF-8'):strlen($currentTitle)?></b>/220</small>
      </label>

      <label class="field-modern">
        <span>Краткое описание</span>
        <textarea name="excerpt" rows="4" maxlength="500" placeholder="2–3 предложения, которые кратко объясняют суть новости" data-excerpt-input><?=e($currentExcerpt)?></textarea>
        <small><b data-excerpt-count><?=function_exists('mb_strlen')?mb_strlen($currentExcerpt,'UTF-8'):strlen($currentExcerpt)?></b>/500</small>
      </label>
    </section>

    <section class="editor-card editor-card-content">
      <div class="editor-section-head">
        <div><span class="section-number">02</span><h3>Текст новости</h3></div>
        <span class="section-help">Основной материал публикации</span>
      </div>
      <div class="editor-toolbar" aria-hidden="true">
        <span><b>B</b></span><span><i>I</i></span><span>H2</span><i class="toolbar-sep"></i><span>• Список</span><span>“ Цитата</span>
      </div>
      <textarea class="content-editor modern-content-editor" name="content" rows="22" placeholder="Начните писать текст новости..."><?=e($currentContent)?></textarea>
    </section>

    <section class="editor-card editor-card-link">
      <div class="editor-section-head">
        <div><span class="section-number">03</span><h3>Адрес материала</h3></div>
        <span class="section-help">Можно оставить пустым</span>
      </div>
      <label class="field-modern">
        <span>URL-адрес</span>
        <div class="url-field"><span><?=e(parse_url(base_url(),PHP_URL_HOST) ?: 'akhikhan.ru')?>/</span><input name="slug" value="<?=e($currentSlug)?>" placeholder="создастся автоматически"></div>
      </label>
    </section>
  </div>

  <aside class="editor-side-column">
    <section class="editor-card editor-publish-card">
      <div class="side-card-title"><span class="side-icon">✓</span><div><h3>Публикация</h3><p>Статус и размещение</p></div></div>

      <label class="field-modern compact">
        <span>Статус</span>
        <select name="status">
          <option value="draft" <?=$currentStatus==='draft'?'selected':''?>>Черновик</option>
          <option value="published" <?=$currentStatus==='published'?'selected':''?>>Опубликовано</option>
        </select>
      </label>

      <label class="field-modern compact">
        <span>Дата и время</span>
        <input type="datetime-local" name="published_at" value="<?=e($currentPublished)?>">
      </label>

      <label class="featured-switch">
        <input type="checkbox" name="is_featured" <?=!empty($article['is_featured'])?'checked':''?>>
        <span class="switch-ui"></span>
        <span><b>Показать в главном блоке</b><small>Сделать материал главным на главной странице</small></span>
      </label>

      <button class="primary wide editor-save-main" type="submit"><span>Сохранить новость</span><b>→</b></button>
      <?php if($id):?><a class="editor-public-link" href="<?=e(article_url($article))?>" target="_blank">Открыть на сайте ↗</a><?php endif;?>
    </section>

    <section class="editor-card editor-category-card">
      <div class="side-card-title"><span class="side-icon">#</span><div><h3>Рубрика</h3><p>Где будет опубликована новость</p></div></div>
      <label class="field-modern compact">
        <span>Выберите рубрику</span>
        <select name="category_id">
          <option value="" <?=$currentCategory===0?'selected':''?>>Главные новости</option>
          <?php foreach($cats as $c):?>
            <option value="<?=$c['id']?>" <?=$currentCategory===(int)$c['id']?'selected':''?>><?=e($c['name'])?></option>
          <?php endforeach;?>
        </select>
      </label>
      <p class="field-hint category-note"><b>Главные новости</b> — материалы без отдельной тематической рубрики. «Новости района» автоматически используются в одноимённом блоке на главной.</p>
      <button class="secondary category-create-toggle" type="button" data-category-toggle>＋ Новая рубрика</button>
      <div class="category-create-box modern-category-create" data-category-create hidden>
        <label class="field-modern compact"><span>Название</span><input name="new_category_name" value="<?=e($_POST['new_category_name']??'')?>" placeholder="Например: Образование"></label>
        <label class="field-modern compact"><span>Описание</span><textarea name="new_category_description" rows="3" placeholder="Необязательно"><?=e($_POST['new_category_description']??'')?></textarea></label>
        <p class="field-hint">Новая рубрика создастся одновременно с сохранением новости.</p>
      </div>
    </section>

    <section class="editor-card editor-cover-card">
      <div class="side-card-title"><span class="side-icon">▧</span><div><h3>Обложка</h3><p>Изображение для карточки и статьи</p></div></div>
      <label class="cover-dropzone" data-cover-zone>
        <input type="file" name="cover" accept="image/jpeg,image/png,image/webp" data-cover-input>
        <span class="cover-drop-icon">＋</span>
        <b><?=!empty($article['cover_image'])?'Заменить изображение':'Добавить изображение'?></b>
        <small>JPG, PNG или WEBP</small>
      </label>
      <div class="cover-preview-shell <?=empty($article['cover_image'])?'is-empty':''?>" data-cover-preview-shell>
        <?php if(!empty($article['cover_image'])):?><img class="cover-preview" src="<?=e(base_url($article['cover_image']))?>" alt="" data-cover-preview><?php else:?><img class="cover-preview" src="" alt="" data-cover-preview hidden><?php endif;?>
      </div>
    </section>
  </aside>
</div>
</form>

<?php require __DIR__.'/_bottom.php'; ?>
