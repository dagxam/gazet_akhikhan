<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_site_admin();

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

    if(isset($_POST['save_order'])){
      $menuOrder=is_array($_POST['menu_order']??null) ? $_POST['menu_order'] : [];
      save_main_menu_order($menuOrder);
      header('Location: '.base_url('admin/main-menu.php?ordered=1'));
      exit;
    }

    $saveId=(int)($_POST['id']??0);
    $label=trim($_POST['label']??'');
    if($label==='') throw new RuntimeException('Введите название пункта меню.');

    $categorySlug=trim($_POST['category_slug']??'');
    $url=trim($_POST['url']??'');
    if($categorySlug!=='') $url='category:'.$categorySlug;
    if($url==='') throw new RuntimeException('Укажите ссылку или выберите рубрику.');

    if($saveId){
      $q=db()->prepare('SELECT sort_order FROM main_menu_items WHERE id=? LIMIT 1');
      $q->execute([$saveId]);
      $sortOrder=(int)($q->fetchColumn() ?: 100);
    }else{
      $sortOrder=(int)db()->query('SELECT COALESCE(MAX(sort_order),0)+10 FROM main_menu_items')->fetchColumn();
    }
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
<?php if(isset($_GET['ordered'])):?><div class="ok">Порядок главного меню сохранён.</div><?php endif;?>

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
        Если выбрана рубрика выше, поле ссылки можно оставить пустым. Для внутренних страниц используйте адрес без домена: <code>contacts.php</code>. Для внешнего сайта — полный <code>https://...</code>. Порядок пунктов меняется в списке справа.
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
    <div class="card-head menu-list-head">
      <div>
        <h2>Пункты верхнего меню</h2>
        <p class="admin-intro">Перетаскивайте пункты за ручку или используйте стрелки. После изменения нажмите «Сохранить порядок».</p>
      </div>
      <?php if($rows):?>
        <form id="menu-order-form" method="post" class="menu-order-save-form">
          <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
          <input type="hidden" name="save_order" value="1">
          <button class="primary" type="submit"><i class="fa-solid fa-check"></i> Сохранить порядок</button>
        </form>
      <?php endif;?>
    </div>

    <?php if($rows):?>
      <div class="menu-preview-bar">
        <?php foreach($rows as $row): if(!$row['is_active']) continue;?>
          <span><?=e($row['label'])?></span>
        <?php endforeach;?>
      </div>

      <div class="menu-items-list" data-main-menu-sortable>
        <?php foreach($rows as $index=>$row):?>
          <article class="menu-admin-item menu-sort-item" draggable="false" data-menu-sort-item>
            <input type="hidden" name="menu_order[]" value="<?=e((string)$row['id'])?>" form="menu-order-form">

            <button class="menu-sort-handle" type="button" data-menu-drag aria-label="Перетащить пункт <?=e($row['label'])?>">
              <i class="fa-solid fa-grip-vertical" aria-hidden="true"></i>
            </button>

            <div class="menu-order-badge" data-menu-number><?=e(str_pad((string)($index+1),2,'0',STR_PAD_LEFT))?></div>

            <div class="menu-admin-copy">
              <strong><i class="menu-state-dot <?=empty($row['is_active'])?'off':''?>"></i><?=e($row['label'])?></strong>
              <small><?=e($row['url'])?><?=$row['open_new_tab']?' · новая вкладка':''?></small>
            </div>

            <span class="menu-admin-state <?=empty($row['is_active'])?'is-off':''?>"><?=empty($row['is_active'])?'Скрыт':'На сайте'?></span>

            <div class="menu-reorder-actions" aria-label="Изменить порядок">
              <button type="button" data-menu-move="up" aria-label="Поднять <?=e($row['label'])?> выше"><i class="fa-solid fa-chevron-up"></i></button>
              <button type="button" data-menu-move="down" aria-label="Опустить <?=e($row['label'])?> ниже"><i class="fa-solid fa-chevron-down"></i></button>
            </div>

            <div class="menu-admin-actions">
              <a class="edit-action" href="<?=e(base_url('admin/main-menu.php?id='.$row['id']))?>">Редактировать</a>
              <form method="post" data-confirm="Удалить этот пункт главного меню?">
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

<script nonce="<?=e(csp_nonce())?>">
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

(function(){
  const list=document.querySelector('[data-main-menu-sortable]');
  if(!list) return;

  let dragging=null;

  function rows(){
    return [...list.querySelectorAll('[data-menu-sort-item]')];
  }

  function syncOrder(){
    const currentRows=rows();
    currentRows.forEach((row,index)=>{
      const number=row.querySelector('[data-menu-number]');
      if(number) number.textContent=String(index+1).padStart(2,'0');

      const up=row.querySelector('[data-menu-move="up"]');
      const down=row.querySelector('[data-menu-move="down"]');
      if(up) up.disabled=index===0;
      if(down) down.disabled=index===currentRows.length-1;
    });

    const preview=document.querySelector('.menu-preview-bar');
    if(preview){
      const labels=new Map();
      currentRows.forEach(row=>{
        const hidden=row.querySelector('input[name="menu_order[]"]');
        const title=row.querySelector('.menu-admin-copy strong');
        const dot=row.querySelector('.menu-state-dot');
        if(hidden && title && !dot?.classList.contains('off')){
          labels.set(hidden.value,title.textContent.trim());
        }
      });
      preview.innerHTML='';
      labels.forEach(label=>{
        const span=document.createElement('span');
        span.textContent=label;
        preview.appendChild(span);
      });
    }
  }

  rows().forEach(row=>{
    const handle=row.querySelector('[data-menu-drag]');
    if(handle){
      handle.addEventListener('mousedown',()=>row.setAttribute('draggable','true'));
      handle.addEventListener('touchstart',()=>row.setAttribute('draggable','true'),{passive:true});
      handle.addEventListener('mouseup',()=>row.setAttribute('draggable','false'));
      handle.addEventListener('touchend',()=>row.setAttribute('draggable','false'));
      handle.addEventListener('keydown',event=>{
        if(event.key==='ArrowUp'){
          event.preventDefault();
          const previous=row.previousElementSibling;
          if(previous) list.insertBefore(row,previous);
          syncOrder();
        }
        if(event.key==='ArrowDown'){
          event.preventDefault();
          const next=row.nextElementSibling;
          if(next) list.insertBefore(next,row);
          syncOrder();
        }
      });
    }

    row.addEventListener('dragstart',event=>{
      dragging=row;
      row.classList.add('is-dragging');
      event.dataTransfer.effectAllowed='move';
      try{event.dataTransfer.setData('text/plain','menu-item');}catch(e){}
    });

    row.addEventListener('dragend',()=>{
      row.classList.remove('is-dragging');
      row.setAttribute('draggable','false');
      rows().forEach(item=>item.classList.remove('is-drag-over'));
      dragging=null;
      syncOrder();
    });

    row.addEventListener('dragover',event=>{
      if(!dragging||dragging===row) return;
      event.preventDefault();
      row.classList.add('is-drag-over');
      const rect=row.getBoundingClientRect();
      if(event.clientY>rect.top+rect.height/2) row.after(dragging);
      else row.before(dragging);
    });

    row.addEventListener('dragleave',()=>row.classList.remove('is-drag-over'));

    row.querySelectorAll('[data-menu-move]').forEach(button=>{
      button.addEventListener('click',()=>{
        if(button.dataset.menuMove==='up'){
          const previous=row.previousElementSibling;
          if(previous) list.insertBefore(row,previous);
        }else{
          const next=row.nextElementSibling;
          if(next) list.insertBefore(next,row);
        }
        syncOrder();
        row.scrollIntoView({block:'nearest',behavior:'smooth'});
      });
    });
  });

  syncOrder();
})();
</script>

<?php require __DIR__.'/_bottom.php'; ?>
