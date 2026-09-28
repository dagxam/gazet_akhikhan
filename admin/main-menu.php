<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$id=(int)($_GET['id']??0);
$editing=null;
$error='';

if($id){
  $q=db()->prepare('SELECT * FROM main_menu_items WHERE id=?');
  $q->execute([$id]);
  $editing=$q->fetch();
  if(!$editing) $id=0;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
  verify_csrf();

  try{
    if(isset($_POST['delete_id'])){
      $deleteId=(int)$_POST['delete_id'];
      db()->prepare('DELETE FROM main_menu_items WHERE id=?')->execute([$deleteId]);
      header('Location: '.base_url('admin/main-menu.php?deleted=1'));
      exit;
    }

    $saveId=(int)($_POST['id']??0);
    $label=trim($_POST['label']??'');
    if($label==='') throw new RuntimeException('Введите название пункта меню.');

    $categorySlug=trim($_POST['category_slug']??'');
    $url=trim($_POST['url']??'');
    if($categorySlug!=='') $url='category:'.$categorySlug;
    if($url==='') throw new RuntimeException('Укажите ссылку или выберите рубрику.');

    $sortOrder=(int)($_POST['sort_order']??100);
    $sortOrder=max(-9999,min(9999,$sortOrder));
    $isActive=isset($_POST['is_active'])?1:0;
    $openNew=isset($_POST['open_new_tab'])?1:0;

    if($saveId){
      $q=db()->prepare('UPDATE main_menu_items SET label=?,url=?,sort_order=?,is_active=?,open_new_tab=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
      $q->execute([$label,$url,$sortOrder,$isActive,$openNew,$saveId]);
      $savedId=$saveId;
    }else{
      $q=db()->prepare('INSERT INTO main_menu_items(label,url,sort_order,is_active,open_new_tab) VALUES(?,?,?,?,?)');
      $q->execute([$label,$url,$sortOrder,$isActive,$openNew]);
      $savedId=(int)db()->lastInsertId();
    }

    header('Location: '.base_url('admin/main-menu.php?id='.$savedId.'&saved=1'));
    exit;
  }catch(Throwable $e){
    $error=$e->getMessage();
  }
}

if($id){
  $q=db()->prepare('SELECT * FROM main_menu_items WHERE id=?');
  $q->execute([$id]);
  $editing=$q->fetch();
}

$rows=main_menu_items(false);
$cats=categories();
$currentCategory='';
$currentUrl=$editing['url']??'';
if(str_starts_with((string)$currentUrl,'category:')){
  $currentCategory=substr((string)$currentUrl,9);
  $currentUrl='';
}

$adminTitle='Главное меню';
require __DIR__.'/_top.php';
?>
<?php if($error):?><div class="error"><?=e($error)?></div><?php endif;?>
<?php if(isset($_GET['saved'])):?><div class="ok">Пункт меню сохранён.</div><?php endif;?>
<?php if(isset($_GET['deleted'])):?><div class="ok">Пункт меню удалён.</div><?php endif;?>

<div class="menu-admin-head">
  <div>
    <span class="editor-eyebrow">Навигация сайта</span>
    <h2>Главное меню</h2>
    <p>Управляйте пунктами верхнего меню сайта: добавляйте новые ссылки, меняйте порядок и временно скрывайте ненужные пункты.</p>
  </div>
  <?php if($editing):?><a class="editor-back" href="<?=e(base_url('admin/main-menu.php'))?>">＋ Новый пункт</a><?php endif;?>
</div>

<div class="menu-admin-layout">
  <section class="editor-card menu-editor-card">
    <div class="side-card-title">
      <span class="side-icon">☰</span>
      <div>
        <h3><?=$editing?'Редактировать пункт':'Добавить пункт меню'?></h3>
        <p>Пункт появится в верхней навигации сайта</p>
      </div>
    </div>

    <form method="post">
      <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
      <input type="hidden" name="id" value="<?=e((string)($editing['id']??0))?>">

      <label class="field-modern compact">
        <span>Название пункта</span>
        <input name="label" required maxlength="120" value="<?=e($editing['label']??'')?>" placeholder="Например: Документы">
      </label>

      <label class="field-modern compact">
        <span>Рубрика сайта</span>
        <select name="category_slug" data-menu-category>
          <option value="">Не привязывать к рубрике</option>
          <?php foreach($cats as $cat):?>
            <option value="<?=e($cat['slug'])?>" <?=$currentCategory===$cat['slug']?'selected':''?>><?=e($cat['name'])?></option>
          <?php endforeach;?>
        </select>
      </label>

      <label class="field-modern compact">
        <span>Ссылка</span>
        <input name="url" value="<?=e($currentUrl)?>" placeholder="Например: documents.php или https://...">
      </label>

      <div class="menu-help">
        Если выбрана рубрика выше, поле ссылки можно оставить пустым. Для внутренних страниц используйте адрес без домена: <code>contacts.php</code>. Для внешнего сайта — полный <code>https://...</code>.
      </div>

      <div class="menu-form-row">
        <label class="field-modern compact">
          <span>Порядок</span>
          <input type="number" name="sort_order" value="<?=e((string)($editing['sort_order']??100))?>" min="-9999" max="9999">
        </label>
        <div></div>
      </div>

      <div class="menu-checks">
        <label class="menu-check">
          <input type="checkbox" name="is_active" <?=!$editing || !empty($editing['is_active'])?'checked':''?>>
          <span>Показывать в меню</span>
        </label>
        <label class="menu-check">
          <input type="checkbox" name="open_new_tab" <?=!empty($editing['open_new_tab'])?'checked':''?>>
          <span>Открывать в новой вкладке</span>
        </label>
      </div>

      <button class="primary wide newspaper-save" type="submit"><?=$editing?'Сохранить изменения':'Добавить в главное меню'?></button>
    </form>
  </section>

  <section class="editor-card menu-list-card">
    <div class="card-head">
      <div>
        <h2>Пункты верхнего меню</h2>
        <p class="admin-intro">Порядок на сайте определяется числом «Порядок»: чем меньше число, тем левее пункт.</p>
      </div>
    </div>

    <?php if($rows):?>
      <div class="menu-preview-bar">
        <?php foreach($rows as $row): if(!$row['is_active']) continue;?>
          <span><?=e($row['label'])?></span>
        <?php endforeach;?>
      </div>

      <div class="menu-items-list">
        <?php foreach($rows as $row):?>
          <article class="menu-admin-item">
            <div class="menu-order-badge"><?=e((string)$row['sort_order'])?></div>
            <div class="menu-admin-copy">
              <strong><i class="menu-state-dot <?=empty($row['is_active'])?'off':''?>"></i><?=e($row['label'])?></strong>
              <small><?=e($row['url'])?><?=$row['open_new_tab']?' · новая вкладка':''?></small>
            </div>
            <div class="menu-admin-actions">
              <a class="edit-action" href="<?=e(base_url('admin/main-menu.php?id='.$row['id']))?>">Редактировать</a>
              <form method="post" onsubmit="return confirm('Удалить этот пункт главного меню?')">
                <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                <input type="hidden" name="delete_id" value="<?=$row['id']?>">
                <button class="danger">Удалить</button>
              </form>
            </div>
          </article>
        <?php endforeach;?>
      </div>
    <?php else:?>
      <div class="menu-admin-empty">
        <div>
          <b>Меню пока пустое</b>
          <p>Добавьте первый пункт через форму слева.</p>
        </div>
      </div>
    <?php endif;?>
  </section>
</div>

<script>
const categorySelect=document.querySelector('[data-menu-category]');
const urlInput=document.querySelector('input[name="url"]');
if(categorySelect&&urlInput){
  const sync=()=>{
    const hasCategory=categorySelect.value!=='';
    urlInput.disabled=hasCategory;
    urlInput.placeholder=hasCategory?'Ссылка будет создана из выбранной рубрики':'Например: documents.php или https://...';
  };
  categorySelect.addEventListener('change',sync);
  sync();
}
</script>

<?php require __DIR__.'/_bottom.php'; ?>
