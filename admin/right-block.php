<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();
ensure_right_blocks_area_schema();

$error='';
$area=in_array($_GET['area']??'home',['home','pages'],true)?$_GET['area']:'home';
$id=(int)($_GET['id']??0);
$editing=null;

if($id){
  $q=db()->prepare('SELECT * FROM homepage_right_blocks WHERE id=? LIMIT 1');
  $q->execute([$id]);
  $editing=$q->fetch();
  if($editing){
    $area=in_array($editing['area']??'home',['home','pages'],true)?$editing['area']:'home';
  }else{
    $id=0;
  }
}

if($_SERVER['REQUEST_METHOD']==='POST'){
  verify_csrf();

  try{
    $action=(string)($_POST['action']??'save');
    $postArea=in_array($_POST['area']??'home',['home','pages'],true)?$_POST['area']:'home';

    if($action==='delete'){
      $deleteId=(int)($_POST['id']??0);
      $q=db()->prepare('SELECT image,area FROM homepage_right_blocks WHERE id=? LIMIT 1');
      $q->execute([$deleteId]);
      $row=$q->fetch();
      if($row){
        safe_delete_homepage_right_block_image((string)($row['image']??''));
        db()->prepare('DELETE FROM homepage_right_blocks WHERE id=?')->execute([$deleteId]);
        $postArea=in_array($row['area']??'home',['home','pages'],true)?$row['area']:'home';
      }
      header('Location: '.base_url('admin/right-block.php?area='.$postArea.'&deleted=1'));
      exit;
    }

    $saveId=(int)($_POST['id']??0);
    $current=null;
    if($saveId){
      $q=db()->prepare('SELECT * FROM homepage_right_blocks WHERE id=? LIMIT 1');
      $q->execute([$saveId]);
      $current=$q->fetch();
      if(!$current) throw new RuntimeException('Блок не найден.');
    }

    $kicker=trim($_POST['kicker']??'');
    $title=trim($_POST['title']??'');
    $body=sanitize_rich_text($_POST['body']??'');
    $linkText=trim($_POST['link_text']??'');
    $linkUrl=trim($_POST['link_url']??'');
    $style=in_array($_POST['style']??'light',['light','accent','dark'],true)?$_POST['style']:'light';
    $sortOrder=max(-9999,min(9999,(int)($_POST['sort_order']??100)));
    $isActive=isset($_POST['is_active'])?1:0;

    if($title==='') throw new RuntimeException('Введите заголовок блока.');

    $oldImage=(string)($current['image']??'');
    if(isset($_POST['remove_image'])){
      safe_delete_homepage_right_block_image($oldImage);
      $image='';
    }else{
      $image=handle_cover_upload($_FILES['image']??[],$oldImage ?: null) ?? '';
      if($oldImage!=='' && $image!==$oldImage) safe_delete_homepage_right_block_image($oldImage);
    }

    if($saveId){
      $q=db()->prepare('UPDATE homepage_right_blocks SET area=?,kicker=?,title=?,body=?,image=?,link_text=?,link_url=?,style=?,sort_order=?,is_active=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
      $q->execute([
        $postArea,
        $kicker!==''?$kicker:null,
        $title,
        $body!==''?$body:null,
        $image!==''?$image:null,
        $linkText!==''?$linkText:null,
        $linkUrl!==''?$linkUrl:null,
        $style,
        $sortOrder,
        $isActive,
        $saveId
      ]);
      $savedId=$saveId;
    }else{
      $q=db()->prepare('INSERT INTO homepage_right_blocks(area,kicker,title,body,image,link_text,link_url,style,sort_order,is_active) VALUES(?,?,?,?,?,?,?,?,?,?)');
      $q->execute([
        $postArea,
        $kicker!==''?$kicker:null,
        $title,
        $body!==''?$body:null,
        $image!==''?$image:null,
        $linkText!==''?$linkText:null,
        $linkUrl!==''?$linkUrl:null,
        $style,
        $sortOrder,
        $isActive
      ]);
      $savedId=(int)db()->lastInsertId();
    }

    header('Location: '.base_url('admin/right-block.php?area='.$postArea.'&id='.$savedId.'&saved=1'));
    exit;
  }catch(Throwable $e){
    $error=$e->getMessage();
  }
}

if($id){
  $q=db()->prepare('SELECT * FROM homepage_right_blocks WHERE id=? LIMIT 1');
  $q->execute([$id]);
  $editing=$q->fetch();
  if($editing) $area=in_array($editing['area']??'home',['home','pages'],true)?$editing['area']:'home';
}

try{
  $blocks=right_blocks($area,false);
}catch(Throwable $e){
  error_log('[right blocks admin list] '.$e->getMessage());
  $blocks=[];
  if($error==='') $error='Не удалось загрузить правые блоки. Структура базы была перепроверена; обновите страницу и повторите.';
}
$areaTitle=$area==='home'?'Главная страница':'Статичные страницы';
$areaDescription=$area==='home'
  ? 'Эти карточки выводятся в правой колонке главной страницы рядом с региональными и спортивными новостями.'
  : 'Эти карточки выводятся в правой колонке всех созданных статичных страниц.';
$adminTitle='Правые блоки';
require __DIR__.'/_top.php';
?>

<?php if($error):?><div class="error"><?=e($error)?></div><?php endif;?>
<?php if(isset($_GET['saved'])):?><div class="ok">Блок сохранён в разделе «<?=e($areaTitle)?>».</div><?php endif;?>
<?php if(isset($_GET['deleted'])):?><div class="ok">Блок удалён.</div><?php endif;?>

<div class="right-blocks-admin-head">
  <div>
    <span class="editor-eyebrow">Оформление сайта</span>
    <h2>Правые блоки</h2>
    <p>Управляйте правой колонкой отдельно для главной страницы и для статичных страниц сайта.</p>
  </div>
  <?php if($editing):?><a class="editor-back" href="<?=e(base_url('admin/right-block.php?area='.$area))?>">＋ Новый блок</a><?php endif;?>
</div>

<nav class="right-block-area-tabs" aria-label="Расположение правых блоков">
  <a class="<?=$area==='home'?'is-active':''?>" href="<?=e(base_url('admin/right-block.php?area=home'))?>">
    <i class="fa-solid fa-house"></i>
    <span><b>На главной</b><small>Правая колонка главной страницы</small></span>
  </a>
  <a class="<?=$area==='pages'?'is-active':''?>" href="<?=e(base_url('admin/right-block.php?area=pages'))?>">
    <i class="fa-regular fa-file-lines"></i>
    <span><b>На страницах</b><small>Правая колонка статичных страниц</small></span>
  </a>
</nav>

<div class="right-block-area-note">
  <i class="<?=$area==='home'?'fa-solid fa-house':'fa-regular fa-file-lines'?>"></i>
  <div><b><?=e($areaTitle)?></b><span><?=e($areaDescription)?></span></div>
</div>

<div class="right-blocks-admin-layout">
  <section class="editor-card right-block-editor">
    <div class="side-card-title">
      <span class="side-icon">＋</span>
      <div>
        <h3><?=$editing?'Редактировать блок':'Добавить блок справа'?></h3>
        <p><?=e($areaTitle)?></p>
      </div>
    </div>

    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?=e((string)($editing['id']??0))?>">
      <input type="hidden" name="area" value="<?=e($area)?>">

      <div class="right-block-switch">
        <span class="right-block-switch-copy">
          <b>Показывать блок</b>
          <small>Выключенный блок останется в админке, но исчезнет с сайта.</small>
        </span>
        <input type="checkbox" name="is_active" value="1" <?=!$editing || !empty($editing['is_active'])?'checked':''?>>
      </div>

      <div class="right-block-form-row">
        <label class="field-modern compact">
          <span>Подпись</span>
          <input name="kicker" maxlength="100" value="<?=e($editing['kicker']??'')?>" placeholder="Например: От редакции">
        </label>
        <label class="field-modern compact">
          <span>Стиль</span>
          <select name="style">
            <option value="light" <?=($editing['style']??'light')==='light'?'selected':''?>>Светлый</option>
            <option value="accent" <?=($editing['style']??'')==='accent'?'selected':''?>>Акцентный</option>
            <option value="dark" <?=($editing['style']??'')==='dark'?'selected':''?>>Тёмный</option>
          </select>
        </label>
      </div>

      <label class="field-modern compact">
        <span>Заголовок</span>
        <input name="title" required maxlength="255" value="<?=e($editing['title']??'')?>" placeholder="Заголовок блока">
      </label>

      <label class="field-modern">
        <span>Текст</span>
        <textarea name="body" rows="5" data-rich-text placeholder="Текст блока"><?=e($editing['body']??'')?></textarea>
      </label>

      <div class="right-block-form-row">
        <label class="field-modern compact">
          <span>Текст кнопки</span>
          <input name="link_text" maxlength="100" value="<?=e($editing['link_text']??'')?>" placeholder="Подробнее">
        </label>
        <label class="field-modern compact">
          <span>Ссылка</span>
          <input name="link_url" maxlength="500" value="<?=e($editing['link_url']??'')?>" placeholder="page/o-redakcii или https://...">
        </label>
      </div>

      <label class="field-modern compact">
        <span>Порядок</span>
        <input type="number" name="sort_order" min="-9999" max="9999" value="<?=e((string)($editing['sort_order']??100))?>">
        <small>Чем меньше число, тем выше карточка.</small>
      </label>

      <label class="field-modern">
        <span>Фоновое изображение</span>
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
        <small>JPG, PNG или WEBP · до 8 МБ.</small>
      </label>

      <?php if($editing && !empty($editing['image'])):?>
        <div class="right-block-image-preview"><img src="<?=e(base_url($editing['image']))?>" alt=""></div>
        <label class="menu-check"><input type="checkbox" name="remove_image" value="1"><span>Удалить изображение</span></label>
      <?php endif;?>

      <button class="primary wide" type="submit"><?=$editing?'Сохранить изменения':'Добавить блок'?></button>
    </form>
  </section>

  <section class="editor-card right-blocks-library">
    <div class="card-head">
      <div>
        <h2><?=e($areaTitle)?></h2>
        <p class="admin-intro">Блоки выводятся сверху вниз по значению «Порядок».</p>
      </div>
    </div>

    <?php if($blocks):?>
      <div class="right-blocks-list">
        <?php foreach($blocks as $block):?>
          <article class="right-block-admin-item <?=empty($block['is_active'])?'is-disabled':''?>">
            <div class="right-block-admin-preview <?=e($block['style'])?> <?=!empty($block['image'])?'has-image':''?>" <?php if(!empty($block['image'])):?>style="background-image:url('<?=e(base_url($block['image']))?>')"<?php endif;?>>
              <span><?=e($block['kicker'] ?: 'Блок')?></span>
              <strong><?=e($block['title'])?></strong>
            </div>
            <div class="right-block-admin-meta">
              <div>
                <span class="status <?=!empty($block['is_active'])?'green':'gray'?>"><?=!empty($block['is_active'])?'Показывается':'Скрыт'?></span>
                <small>Порядок: <?=e((string)$block['sort_order'])?> · <?=e($block['style'])?></small>
              </div>
              <div class="row-actions">
                <a class="edit-action" href="<?=e(base_url('admin/right-block.php?area='.$area.'&id='.$block['id']))?>">Редактировать</a>
                <form method="post" onsubmit="return confirm('Удалить этот блок?')">
                  <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="area" value="<?=e($area)?>">
                  <input type="hidden" name="id" value="<?=$block['id']?>">
                  <button class="danger" type="submit">Удалить</button>
                </form>
              </div>
            </div>
          </article>
        <?php endforeach;?>
      </div>
    <?php else:?>
      <div class="right-blocks-empty">
        <b>В этом разделе блоков пока нет</b>
        <p><?=$area==='home'?'Добавьте карточку для правой колонки главной страницы.':'Добавьте карточку — она появится справа на статичных страницах.'?></p>
      </div>
    <?php endif;?>
  </section>
</div>

<?php require __DIR__.'/_bottom.php'; ?>
