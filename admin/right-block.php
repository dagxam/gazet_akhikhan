<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_site_admin();
ensure_right_blocks_area_schema();

$error='';
$requestedArea=(string)($_GET['area']??'');
$areaSelected=in_array($requestedArea,['home','pages'],true);
$area=$areaSelected ? $requestedArea : '';
$id=(int)($_GET['id']??0);
$editing=null;

if($id){
  $q=db()->prepare('SELECT * FROM homepage_right_blocks WHERE id=? LIMIT 1');
  $q->execute([$id]);
  $editing=$q->fetch();
  if($editing){
    $area=in_array($editing['area']??'home',['home','pages'],true)?$editing['area']:'home';
    $areaSelected=true;
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
    $area=in_array($_POST['area']??'home',['home','pages'],true)?$_POST['area']:'home';
    $areaSelected=true;
  }
}

if($id){
  $q=db()->prepare('SELECT * FROM homepage_right_blocks WHERE id=? LIMIT 1');
  $q->execute([$id]);
  $editing=$q->fetch();
  if($editing){
    $area=in_array($editing['area']??'home',['home','pages'],true)?$editing['area']:'home';
    $areaSelected=true;
  }
}

$homeBlocks=[];
$pageBlocks=[];
try{
  $homeBlocks=right_blocks('home',false);
  $pageBlocks=right_blocks('pages',false);
}catch(Throwable $e){
  error_log('[right blocks admin counts] '.$e->getMessage());
}

$blocks=[];
$blocksPager=['items'=>[],'page'=>1,'total_pages'=>1,'total'=>0];
if($areaSelected){
  $blocks=$area==='pages' ? $pageBlocks : $homeBlocks;
  $blocksPager=admin_paginate_array($blocks,10,'page');
  $blocks=$blocksPager['items'];
}

$areaTitle=$area==='pages'?'На страницах':'На главной';
$areaSubtitle=$area==='pages'?'Статичные страницы':'Главная страница';
$areaDescription=$area==='pages'
  ? 'Здесь находятся блоки, которые показываются в правой колонке статичных страниц.'
  : 'Здесь находятся блоки правой колонки главной страницы рядом с новостными разделами.';

$adminTitle='Правые блоки';
require __DIR__.'/_top.php';
?>

<?php if($error):?><div class="error"><?=e($error)?></div><?php endif;?>
<?php if(isset($_GET['saved']) && $areaSelected):?><div class="ok">Блок сохранён в разделе «<?=e($areaTitle)?>».</div><?php endif;?>
<?php if(isset($_GET['deleted']) && $areaSelected):?><div class="ok">Блок удалён.</div><?php endif;?>

<div class="right-blocks-admin-head">
  <div>
    <span class="editor-eyebrow">Оформление сайта</span>
    <h2><?=$areaSelected?e($areaTitle):'Правые блоки'?></h2>
    <p><?=$areaSelected?e($areaDescription):'Сначала выберите, где будут размещаться блоки. Каждый раздел настраивается отдельно.'?></p>
  </div>

  <?php if($areaSelected):?>
    <div class="right-block-head-actions">
      <a class="editor-back right-block-back" href="<?=e(base_url('admin/right-block.php'))?>">
        <i class="fa-solid fa-arrow-left"></i> Выбор раздела
      </a>
      <?php if($editing):?>
        <a class="secondary" href="<?=e(base_url('admin/right-block.php?area='.$area))?>">＋ Новый блок</a>
      <?php endif;?>
    </div>
  <?php endif;?>
</div>

<?php if(!$areaSelected):?>
  <section class="right-block-hub" aria-label="Выбор расположения правых блоков">
    <a class="right-block-destination is-home" href="<?=e(base_url('admin/right-block.php?area=home'))?>">
      <span class="right-block-destination-art">
        <i class="fa-solid fa-house"></i>
        <span class="right-block-destination-lines"><i></i><i></i><i></i></span>
        <b class="right-block-destination-sidebar"></b>
      </span>
      <span class="right-block-destination-copy">
        <small>01 · Главная страница</small>
        <strong>На главной</strong>
        <span>Добавление и управление блоками в правой колонке главной страницы.</span>
      </span>
      <span class="right-block-destination-foot">
        <b><?=count($homeBlocks)?> <?=count($homeBlocks)===1?'блок':'блоков'?></b>
        <i class="fa-solid fa-arrow-right"></i>
      </span>
    </a>

    <a class="right-block-destination is-pages" href="<?=e(base_url('admin/right-block.php?area=pages'))?>">
      <span class="right-block-destination-art">
        <i class="fa-regular fa-file-lines"></i>
        <span class="right-block-destination-lines"><i></i><i></i><i></i></span>
        <b class="right-block-destination-sidebar"></b>
      </span>
      <span class="right-block-destination-copy">
        <small>02 · Статичные страницы</small>
        <strong>На страницах</strong>
        <span>Отдельные блоки, которые выводятся справа на созданных статичных страницах.</span>
      </span>
      <span class="right-block-destination-foot">
        <b><?=count($pageBlocks)?> <?=count($pageBlocks)===1?'блок':'блоков'?></b>
        <i class="fa-solid fa-arrow-right"></i>
      </span>
    </a>
  </section>

  <div class="right-block-hub-note">
    <i class="fa-solid fa-circle-info"></i>
    <div>
      <b>Разделы независимы</b>
      <span>Блоки из «На главной» не попадут на статичные страницы, а блоки из «На страницах» не появятся на главной.</span>
    </div>
  </div>

<?php else:?>

  <nav class="right-block-inner-switch" aria-label="Переключить расположение">
    <a class="<?=$area==='home'?'is-active':''?>" href="<?=e(base_url('admin/right-block.php?area=home'))?>">
      <i class="fa-solid fa-house"></i><span>На главной</span><b><?=count($homeBlocks)?></b>
    </a>
    <a class="<?=$area==='pages'?'is-active':''?>" href="<?=e(base_url('admin/right-block.php?area=pages'))?>">
      <i class="fa-regular fa-file-lines"></i><span>На страницах</span><b><?=count($pageBlocks)?></b>
    </a>
  </nav>

  <section class="right-block-section-banner <?=$area==='pages'?'is-pages':'is-home'?>">
    <span class="right-block-section-icon">
      <i class="<?=$area==='pages'?'fa-regular fa-file-lines':'fa-solid fa-house'?>"></i>
    </span>
    <div>
      <small><?=e($areaSubtitle)?></small>
      <strong><?=e($areaTitle)?></strong>
      <p><?=e($areaDescription)?></p>
    </div>
    <span class="right-block-section-count"><b><?=e((string)$blocksPager['total'])?></b><small>всего</small></span>
  </section>

  <div class="right-blocks-admin-layout">
    <section class="editor-card right-block-editor">
      <div class="side-card-title">
        <span class="side-icon">＋</span>
        <div>
          <h3><?=$editing?'Редактировать блок':'Добавить новый блок'?></h3>
          <p><?=e($areaTitle)?> · все настройки этого блока</p>
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
            <small>Выключенный блок сохранится в админке, но исчезнет с сайта.</small>
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

        <div class="right-block-style-guide" aria-label="Варианты оформления">
          <div class="right-block-style-chip light"><b>Светлый</b><small>Бумага и золото</small></div>
          <div class="right-block-style-chip accent"><b>Акцентный</b><small>Бронзовый</small></div>
          <div class="right-block-style-chip dark"><b>Тёмный</b><small>Орех и золото</small></div>
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
          <h2>Созданные блоки</h2>
          <p class="admin-intro"><?=e($areaTitle)?> · выводятся сверху вниз по значению «Порядок».</p>
        </div>
      </div>

      <?php if($blocks):?>
        <div class="right-blocks-list">
          <?php foreach($blocks as $block):?>
            <article class="right-block-admin-item <?=empty($block['is_active'])?'is-disabled':''?>">
              <?php $blockPreviewClass=!empty($block['image']) ? csp_dynamic_class("background-image:url('".base_url($block['image'])."');",'admin-right-block') : ''; ?>
              <div class="right-block-admin-preview <?=e($block['style'])?> <?=!empty($block['image'])?'has-image':''?> <?=e($blockPreviewClass)?>">
                <span><?=e($block['kicker'] ?: 'Блок')?></span>
                <strong><?=e($block['title'])?></strong>
              </div>
              <div class="right-block-admin-meta">
                <div>
                  <span class="status <?=!empty($block['is_active'])?'green':'gray'?>"><?=!empty($block['is_active'])?'Показывается':'Скрыт'?></span>
                  <small>Порядок: <?=e((string)$block['sort_order'])?> · <?=e(match($block['style']){'accent'=>'Акцентный','dark'=>'Тёмный',default=>'Светлый'})?></small>
                </div>
                <div class="row-actions">
                  <a class="edit-action" href="<?=e(base_url('admin/right-block.php?area='.$area.'&id='.$block['id']))?>">Редактировать</a>
                  <form method="post" data-confirm="Удалить этот блок?">
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
        <?php render_admin_pagination('admin/right-block.php',$blocksPager['page'],$blocksPager['total_pages'],['area'=>$area],'page','Страницы правых блоков'); ?>
      <?php else:?>
        <div class="right-blocks-empty">
          <b>Здесь пока нет блоков</b>
          <p><?=$area==='home'?'Создайте первый блок для правой колонки главной страницы.':'Создайте первый блок для правой колонки статичных страниц.'?></p>
        </div>
      <?php endif;?>
    </section>
  </div>
<?php endif;?>

<?php require __DIR__.'/_bottom.php'; ?>
