<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$error='';

$defaults=[
  'right_block_enabled'=>'1',
  'right_block_kicker'=>'От редакции',
  'right_block_title'=>'О районе — с уважением к людям и истории',
  'right_block_text'=>setting('editor_note','Наша задача — рассказывать о важном для жителей района, сохранять память о прошлом и показывать людей, которые сегодня меняют родной край.'),
  'right_block_image'=>'',
  'right_block_link_text'=>'',
  'right_block_link_url'=>'',
  'right_block_style'=>'light',
];

if($_SERVER['REQUEST_METHOD']==='POST'){
  verify_csrf();

  try{
    $enabled=isset($_POST['right_block_enabled'])?'1':'0';
    $kicker=trim($_POST['right_block_kicker']??'');
    $title=trim($_POST['right_block_title']??'');
    $text=trim($_POST['right_block_text']??'');
    $linkText=trim($_POST['right_block_link_text']??'');
    $linkUrl=trim($_POST['right_block_link_url']??'');
    $style=in_array($_POST['right_block_style']??'light',['light','dark','accent'],true)
      ? $_POST['right_block_style']
      : 'light';

    if($title==='') throw new RuntimeException('Введите заголовок правого блока.');

    $oldImage=setting('right_block_image','');
    if(isset($_POST['remove_image'])){
      $image='';
    }else{
      $image=handle_cover_upload($_FILES['right_block_image']??[],$oldImage ?: null) ?? '';
    }

    save_setting('right_block_enabled',$enabled);
    save_setting('right_block_kicker',$kicker);
    save_setting('right_block_title',$title);
    save_setting('right_block_text',$text);
    save_setting('right_block_image',$image);
    save_setting('right_block_link_text',$linkText);
    save_setting('right_block_link_url',$linkUrl);
    save_setting('right_block_style',$style);

    header('Location: '.base_url('admin/right-block.php?saved=1'));
    exit;
  }catch(Throwable $e){
    $error=$e->getMessage();
  }
}

$values=[];
foreach($defaults as $key=>$default){
  $values[$key]=setting($key,$default);
}

$adminTitle='Правый блок';
require __DIR__.'/_top.php';
?>

<?php if($error):?><div class="error"><?=e($error)?></div><?php endif;?>
<?php if(isset($_GET['saved'])):?><div class="ok">Правый блок сохранён. Изменения уже используются на главной странице.</div><?php endif;?>

<div class="right-block-admin-head">
  <div>
    <span class="editor-eyebrow">Главная страница</span>
    <h2>Правый блок</h2>
    <p>Настройте верхний блок в правой колонке раздела «Новости района»: текст, изображение, ссылку и визуальный стиль.</p>
  </div>
</div>

<form method="post" enctype="multipart/form-data" class="right-block-admin-grid">
  <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">

  <section class="editor-card right-block-settings-card">
    <div class="right-block-switch">
      <span class="right-block-switch-copy">
        <b>Показывать правый блок</b>
        <small>Если выключить, блок исчезнет с главной страницы.</small>
      </span>
      <input type="checkbox" name="right_block_enabled" value="1" <?=$values['right_block_enabled']==='1'?'checked':''?>>
    </div>

    <div class="right-block-form-row">
      <label class="field-modern compact">
        <span>Подпись над заголовком</span>
        <input name="right_block_kicker" maxlength="80" value="<?=e($values['right_block_kicker'])?>" placeholder="Например: От редакции">
      </label>

      <label class="field-modern compact">
        <span>Стиль блока</span>
        <select name="right_block_style">
          <option value="light" <?=$values['right_block_style']==='light'?'selected':''?>>Светлый</option>
          <option value="accent" <?=$values['right_block_style']==='accent'?'selected':''?>>Акцентный</option>
          <option value="dark" <?=$values['right_block_style']==='dark'?'selected':''?>>Тёмный</option>
        </select>
      </label>
    </div>

    <label class="field-modern">
      <span>Заголовок</span>
      <input name="right_block_title" required maxlength="220" value="<?=e($values['right_block_title'])?>">
    </label>

    <label class="field-modern">
      <span>Текст</span>
      <textarea name="right_block_text" rows="6" maxlength="1200"><?=e($values['right_block_text'])?></textarea>
    </label>

    <div class="right-block-form-row">
      <label class="field-modern compact">
        <span>Текст кнопки</span>
        <input name="right_block_link_text" maxlength="80" value="<?=e($values['right_block_link_text'])?>" placeholder="Например: Подробнее">
      </label>

      <label class="field-modern compact">
        <span>Ссылка кнопки</span>
        <input name="right_block_link_url" maxlength="500" value="<?=e($values['right_block_link_url'])?>" placeholder="about.php или https://...">
      </label>
    </div>

    <label class="field-modern">
      <span>Фоновое изображение</span>
      <input type="file" name="right_block_image" accept="image/jpeg,image/png,image/webp">
      <small>JPG, PNG или WEBP · до 8 МБ. Если добавить изображение, текст автоматически станет светлым.</small>
    </label>

    <?php if(!empty($values['right_block_image'])):?>
      <div class="right-block-image-preview">
        <img src="<?=e(base_url($values['right_block_image']))?>" alt="Текущее изображение правого блока">
      </div>
      <label class="menu-check">
        <input type="checkbox" name="remove_image" value="1">
        <span>Удалить изображение из блока</span>
      </label>
    <?php else:?>
      <div class="right-block-image-preview">
        <div class="right-block-image-empty">Фоновое изображение пока не установлено</div>
      </div>
    <?php endif;?>

    <button class="primary wide" type="submit">Сохранить правый блок</button>
  </section>

  <aside class="editor-card right-block-preview-card">
    <div class="side-card-title">
      <span class="side-icon">↗</span>
      <div>
        <h3>Предпросмотр</h3>
        <p>Пример расположения содержимого</p>
      </div>
    </div>

    <div class="right-block-live-preview">
      <span><?=e($values['right_block_kicker'] ?: 'Правый блок')?></span>
      <h3><?=e($values['right_block_title'])?></h3>
      <p><?=nl2br(e($values['right_block_text']))?></p>
      <?php if($values['right_block_link_text']!==''):?><b><?=e($values['right_block_link_text'])?> →</b><?php endif;?>
    </div>
  </aside>
</form>

<?php require __DIR__.'/_bottom.php'; ?>
