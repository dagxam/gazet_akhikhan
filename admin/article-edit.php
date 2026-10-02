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
    $categoryIds=array_values(array_unique(array_filter(array_map('intval',(array)($_POST['category_ids']??[])))));

    $newCategoryName=trim($_POST['new_category_name']??'');
    $newCategoryDescription=sanitize_rich_text($_POST['new_category_description']??'');

    $excerpt=sanitize_rich_text($_POST['excerpt']??'');
    $locationRegion=trim($_POST['location_region']??'');
    $locationCity=trim($_POST['location_city']??'');
    if(function_exists('mb_substr')){
      $locationRegion=mb_substr($locationRegion,0,160,'UTF-8');
      $locationCity=mb_substr($locationCity,0,160,'UTF-8');
    }else{
      $locationRegion=substr($locationRegion,0,160);
      $locationCity=substr($locationCity,0,160);
    }
    $content=sanitize_rich_text($_POST['content']??'');
    $status=in_array($_POST['status']??'draft',['draft','published'],true)?$_POST['status']:'draft';
    $featured=isset($_POST['is_featured'])?1:0;
    $publishedAt=trim($_POST['published_at']??'');
    $publishedAt=$publishedAt?str_replace('T',' ',$publishedAt).(strlen($publishedAt)===16?':00':''):null;
    $cover=handle_cover_upload($_FILES['cover']??[], $article['cover_image']??null);
    $removeArticleImages=(array)($_POST['remove_article_images']??[]);
    $articleImageFiles=$_FILES['article_images']??[];

    $pdo=db();
    $pdo->beginTransaction();
    try{
      if($newCategoryName!==''){
        $categoryIds[]=find_or_create_category($newCategoryName,$newCategoryDescription);
        $categoryIds=array_values(array_unique($categoryIds));
      }
      if(!$categoryIds) throw new RuntimeException('Выберите хотя бы одну рубрику.');

      if($featured){
        $pdo->exec('UPDATE articles SET is_featured=0');
      }

      if($id){
        $q=$pdo->prepare('UPDATE articles SET title=?,slug=?,excerpt=?,location_region=?,location_city=?,content=?,cover_image=?,status=?,is_featured=?,published_at=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
        $q->execute([$title,$slug,$excerpt,$locationRegion!==''?$locationRegion:null,$locationCity!==''?$locationCity:null,$content,$cover,$status,$featured,$publishedAt,$id]);
      } else {
        $q=$pdo->prepare('INSERT INTO articles(category_id,author_id,title,slug,excerpt,location_region,location_city,content,cover_image,status,is_featured,published_at) VALUES(NULL,?,?,?,?,?,?,?,?,?,?,?)');
        $q->execute([admin_user()['id'],$title,$slug,$excerpt,$locationRegion!==''?$locationRegion:null,$locationCity!==''?$locationCity:null,$content,$cover,$status,$featured,$publishedAt]);
        $id=(int)$pdo->lastInsertId();
      }

      set_article_categories($id,$categoryIds);
      remove_article_images($id,$removeArticleImages);
      handle_article_image_uploads($id,$articleImageFiles,12);
      $pdo->commit();
    }catch(Throwable $e){
      if($pdo->inTransaction()) $pdo->rollBack();
      throw $e;
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
$mainCategoryId=category_id_by_slug('glavnye-novosti');
if($_SERVER['REQUEST_METHOD']==='POST'){
  $currentCategories=array_values(array_unique(array_filter(array_map('intval',(array)($_POST['category_ids']??[])))));
} elseif($id){
  $currentCategories=article_category_ids($id);
} else {
  $currentCategories=$mainCategoryId?[$mainCategoryId]:[];
}

$adminTitle=$id?'Редактирование новости':'Новая новость';
require __DIR__.'/_top.php';

$currentStatus=$article['status']??'published';
$currentTitle=$article['title']??'';
$currentExcerpt=$article['excerpt']??'';
$currentLocationRegion=$_SERVER['REQUEST_METHOD']==='POST' ? trim($_POST['location_region']??'') : ($article['location_region']??'');
$currentLocationCity=$_SERVER['REQUEST_METHOD']==='POST' ? trim($_POST['location_city']??'') : ($article['location_city']??'');
$currentContent=$article['content']??'';
$currentSlug=$article['slug']??'';
$currentPublished=!empty($article['published_at'])?date('Y-m-d\\TH:i',strtotime($article['published_at'])):'';
$currentImages=$id ? article_images($id) : [];
?>
<?php if($error):?><div class="error"><?=e($error)?></div><?php endif;?>
<?php if(isset($_GET['saved'])):?><div class="ok editor-notice">Изменения сохранены.</div><?php endif;?>

<form class="editor-form editorial-editor" method="post" enctype="multipart/form-data" data-editor-form>
<input type="hidden" name="csrf" value="<?=e(csrf_token())?>">

<div class="editor-page-head">
  <div>
    <span class="editor-eyebrow"><?=$id?'Редактирование материала':'Новый материал'?></span>
    <h2><?=$id?'Редактирование новости':'Создание новости'?></h2>
    <p>Подготовьте материал, выберите одну или несколько рубрик и настройте публикацию.</p>
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
        <textarea name="excerpt" rows="4" data-rich-text placeholder="2–3 предложения, которые кратко объясняют суть новости" data-excerpt-input><?=e($currentExcerpt)?></textarea>
        <small><b data-excerpt-count><?=function_exists('mb_strlen')?mb_strlen($currentExcerpt,'UTF-8'):strlen($currentExcerpt)?></b>/500</small>
      </label>
    </section>

    <section class="editor-card editor-card-content">
      <div class="editor-section-head">
        <div><span class="section-number">02</span><h3>Текст новости</h3></div>
        <span class="section-help">Основной материал публикации</span>
      </div>
      <textarea class="content-editor modern-content-editor" name="content" data-rich-text rows="22" placeholder="Начните писать текст новости..."><?=e($currentContent)?></textarea>
    </section>

    <section class="editor-card editor-card-gallery">
      <div class="editor-section-head">
        <div><span class="section-number">03</span><h3>Ещё фотографии</h3></div>
        <span class="section-help">До 12 изображений к новости</span>
      </div>

      <label class="article-gallery-dropzone" data-article-gallery-zone>
        <input type="file" name="article_images[]" accept="image/jpeg,image/png,image/webp" multiple data-article-gallery-input>
        <span class="article-gallery-drop-icon"><i class="fa-regular fa-images"></i></span>
        <b>Добавить фотографии к материалу</b>
        <small>Можно выбрать сразу несколько JPG, PNG или WEBP. Они будут красиво собраны в галерею внутри полной новости.</small>
      </label>

      <div class="article-gallery-selected" data-article-gallery-selected hidden></div>

      <?php if($currentImages):?>
        <div class="article-gallery-existing">
          <?php foreach($currentImages as $image):?>
            <label class="article-gallery-existing-item">
              <img src="<?=e(base_url($image['image_path']))?>" alt="">
              <span class="article-gallery-remove">
                <input type="checkbox" name="remove_article_images[]" value="<?=$image['id']?>">
                <b>Удалить</b>
              </span>
            </label>
          <?php endforeach;?>
        </div>
        <p class="field-hint"><i class="fa-solid fa-circle-info"></i> Отметьте «Удалить» у ненужной фотографии и сохраните новость.</p>
      <?php endif;?>
    </section>

    <section class="editor-card editor-card-link">
      <div class="editor-section-head">
        <div><span class="section-number">04</span><h3>Адрес материала</h3></div>
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
        <span><b>Показать в большом блоке</b><small>Эта новость станет большой новостью слева на главной</small></span>
      </label>

      <button class="primary wide editor-save-main" type="submit"><span>Сохранить новость</span><b>→</b></button>
      <?php if($id):?><a class="editor-public-link" href="<?=e(article_url($article))?>" target="_blank">Открыть на сайте ↗</a><?php endif;?>
    </section>

    <section class="editor-card editor-location-card">
      <div class="side-card-title">
        <span class="side-icon"><i class="fa-solid fa-location-dot" aria-hidden="true"></i></span>
        <div>
          <h3>Регион и город</h3>
          <p>Для отметки на главной новости</p>
        </div>
      </div>

      <div class="editor-location-preview">
        <span><i class="fa-solid fa-location-crosshairs"></i> География материала</span>
        <b data-location-preview-city><?=e($currentLocationCity ?: 'Унцукульский район')?></b>
        <small data-location-preview-region><?=e($currentLocationRegion ?: 'Дагестан')?></small>
      </div>

      <label class="field-modern compact">
        <span>Регион</span>
        <input name="location_region" maxlength="160" value="<?=e($currentLocationRegion)?>" placeholder="Например: Республика Дагестан" data-location-region>
        <small>Если не заполнить, на главной останется «Дагестан».</small>
      </label>

      <label class="field-modern compact">
        <span>Город / район / населённый пункт</span>
        <input name="location_city" maxlength="160" value="<?=e($currentLocationCity)?>" placeholder="Например: Унцукульский район" data-location-city>
        <small>Можно указать город, район или село.</small>
      </label>

      <p class="field-hint location-note"><i class="fa-solid fa-circle-info"></i> Поля необязательные и не влияют на рубрики новости.</p>
    </section>

    <section class="editor-card editor-category-card">
      <div class="side-card-title"><span class="side-icon">#</span><div><h3>Рубрики</h3><p>Можно выбрать несколько</p></div></div>
      <div class="category-picker">
        <?php foreach($cats as $c):
          $checked=in_array((int)$c['id'],$currentCategories,true);
          $isMain=$c['slug']==='glavnye-novosti';
        ?>
          <label class="category-option <?=$isMain?'is-main':''?>">
            <input type="checkbox" name="category_ids[]" value="<?=$c['id']?>" <?=$checked?'checked':''?>>
            <span class="category-check">✓</span>
            <span class="category-option-copy">
              <b><?=e($c['name'])?></b>
              <?php if(!empty($c['description'])):?><small><?=e(rich_text_plain($c['description']))?></small><?php endif;?>
            </span>
          </label>
        <?php endforeach;?>
      </div>
      <p class="field-hint category-note">Новость появится во <b>всех выбранных рубриках</b>. «Главные новости» отвечают за правый блок на главной странице.</p>
      <button class="secondary category-create-toggle" type="button" data-category-toggle>＋ Новая рубрика</button>
      <div class="category-create-box modern-category-create" data-category-create hidden>
        <label class="field-modern compact"><span>Название</span><input name="new_category_name" value="<?=e($_POST['new_category_name']??'')?>" placeholder="Например: Образование"></label>
        <label class="field-modern compact"><span>Описание</span><textarea name="new_category_description" rows="3" data-rich-text placeholder="Необязательно"><?=e($_POST['new_category_description']??'')?></textarea></label>
        <p class="field-hint">Новая рубрика создастся и автоматически добавится к этой новости.</p>
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

<script>
(function(){
  const input=document.querySelector('[data-article-gallery-input]');
  const selected=document.querySelector('[data-article-gallery-selected]');
  if(!input||!selected) return;

  input.addEventListener('change',function(){
    selected.innerHTML='';
    const files=[...(input.files||[])].slice(0,12);
    if(!files.length){
      selected.hidden=true;
      return;
    }
    selected.hidden=false;
    files.forEach(function(file){
      if(!file.type.startsWith('image/')) return;
      const item=document.createElement('span');
      item.className='article-gallery-selected-item';
      const img=document.createElement('img');
      img.alt='';
      img.src=URL.createObjectURL(file);
      img.onload=function(){ URL.revokeObjectURL(img.src); };
      const name=document.createElement('small');
      name.textContent=file.name;
      item.append(img,name);
      selected.appendChild(item);
    });
  });
})();
</script>

<?php require __DIR__.'/_bottom.php'; ?>
