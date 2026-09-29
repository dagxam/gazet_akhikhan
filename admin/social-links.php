<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$error='';
$id=(int)($_GET['id']??0);
$editing=null;
$catalog=social_service_catalog();

if($id){
  $q=db()->prepare('SELECT * FROM social_links WHERE id=? LIMIT 1');
  $q->execute([$id]);
  $editing=$q->fetch();
  if(!$editing) $id=0;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
  verify_csrf();

  try{
    $action=(string)($_POST['action']??'save');

    if($action==='delete'){
      $deleteId=(int)($_POST['id']??0);
      db()->prepare('DELETE FROM social_links WHERE id=?')->execute([$deleteId]);
      header('Location: '.base_url('admin/social-links.php?deleted=1'));
      exit;
    }

    $saveId=(int)($_POST['id']??0);
    $service=(string)($_POST['service']??'vk');
    if(!isset($catalog[$service])) $service='custom';

    $label=trim($_POST['label']??'');
    if($label==='') $label=social_service_name($service);

    $url=normalize_social_url($service,(string)($_POST['url']??''));
    if($url==='') throw new RuntimeException('Укажите ссылку или e-mail.');

    $sortOrder=max(-9999,min(9999,(int)($_POST['sort_order']??100)));
    $isActive=isset($_POST['is_active'])?1:0;

    if($saveId){
      $q=db()->prepare('UPDATE social_links SET service=?,label=?,url=?,sort_order=?,is_active=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
      $q->execute([$service,$label,$url,$sortOrder,$isActive,$saveId]);
      $savedId=$saveId;
    }else{
      $q=db()->prepare('INSERT INTO social_links(service,label,url,sort_order,is_active) VALUES(?,?,?,?,?)');
      $q->execute([$service,$label,$url,$sortOrder,$isActive]);
      $savedId=(int)db()->lastInsertId();
    }

    header('Location: '.base_url('admin/social-links.php?id='.$savedId.'&saved=1'));
    exit;
  }catch(Throwable $e){
    $error=$e->getMessage();
  }
}

if($id){
  $q=db()->prepare('SELECT * FROM social_links WHERE id=? LIMIT 1');
  $q->execute([$id]);
  $editing=$q->fetch();
}

$links=social_links(false);
$adminTitle='Мы в соцсетях';
require __DIR__.'/_top.php';
?>

<?php if($error):?><div class="error"><?=e($error)?></div><?php endif;?>
<?php if(isset($_GET['saved'])):?><div class="ok">Ссылка сохранена. Она автоматически используется в top bar и футере.</div><?php endif;?>
<?php if(isset($_GET['deleted'])):?><div class="ok">Ссылка удалена.</div><?php endif;?>

<div class="social-admin-head">
  <div>
    <span class="editor-eyebrow">Контакты сайта</span>
    <h2>Мы в соцсетях</h2>
    <p>Добавляйте ссылки на социальные сети и сервисы редакции. Активные ссылки автоматически появляются в верхней панели сайта и в футере.</p>
  </div>
  <?php if($editing):?><a class="editor-back" href="<?=e(base_url('admin/social-links.php'))?>">＋ Добавить ещё</a><?php endif;?>
</div>

<div class="social-admin-layout">
  <section class="editor-card social-editor-card">
    <div class="side-card-title">
      <span class="side-icon">＠</span>
      <div>
        <h3><?=$editing?'Редактировать ссылку':'Добавить соцсеть'?></h3>
        <p>Выберите сервис и укажите адрес</p>
      </div>
    </div>

    <form method="post">
      <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?=e((string)($editing['id']??0))?>">

      <div class="social-service-grid">
        <?php foreach($catalog as $key=>$meta):?>
          <label class="social-service-option">
            <input type="radio" name="service" value="<?=e($key)?>" <?=($editing['service']??'vk')===$key?'checked':''?>>
            <i class="<?=e($meta['icon'])?>" aria-hidden="true"></i>
            <span><?=e($meta['name'])?></span>
          </label>
        <?php endforeach;?>
      </div>

      <label class="field-modern compact">
        <span>Название</span>
        <input name="label" maxlength="120" value="<?=e($editing['label']??'')?>" placeholder="Можно оставить пустым">
      </label>

      <label class="field-modern compact">
        <span>Ссылка или e-mail</span>
        <input name="url" required maxlength="500" value="<?=e($editing['url']??'')?>" placeholder="https://... или mail@example.ru">
        <small>Для почты можно указать обычный e-mail. Для остальных сервисов — ссылку на страницу или канал.</small>
      </label>

      <label class="field-modern compact">
        <span>Порядок</span>
        <input type="number" name="sort_order" min="-9999" max="9999" value="<?=e((string)($editing['sort_order']??100))?>">
        <small>Чем меньше число, тем левее и выше выводится ссылка.</small>
      </label>

      <label class="menu-check">
        <input type="checkbox" name="is_active" value="1" <?=!$editing || !empty($editing['is_active'])?'checked':''?>>
        <span>Показывать на сайте</span>
      </label>

      <button class="primary wide" type="submit"><?=$editing?'Сохранить изменения':'Добавить соцсеть'?></button>
    </form>
  </section>

  <section class="editor-card social-list-card">
    <div class="card-head">
      <div>
        <h2>Подключённые сервисы</h2>
        <p class="admin-intro">В top bar и футере выводятся только активные ссылки.</p>
      </div>
    </div>

    <?php if($links):?>
      <div class="social-preview-strip" aria-label="Предпросмотр">
        <?php foreach($links as $link): if(empty($link['is_active'])) continue;?>
          <span title="<?=e($link['label'] ?: social_service_name($link['service']))?>"><i class="<?=e(social_service_icon($link['service']))?>"></i></span>
        <?php endforeach;?>
      </div>

      <div class="social-link-list">
        <?php foreach($links as $link):?>
          <article class="social-link-row <?=empty($link['is_active'])?'is-disabled':''?>">
            <span class="social-link-icon"><i class="<?=e(social_service_icon($link['service']))?>" aria-hidden="true"></i></span>
            <div class="social-link-copy">
              <strong><?=e($link['label'] ?: social_service_name($link['service']))?></strong>
              <small><?=e($link['url'])?></small>
              <div class="social-link-meta">
                <span><?=e(social_service_name($link['service']))?></span>
                <span>Порядок: <?=e((string)$link['sort_order'])?></span>
                <span><?=!empty($link['is_active'])?'Активна':'Скрыта'?></span>
              </div>
            </div>
            <div class="social-link-actions">
              <a class="edit-action" href="<?=e(base_url('admin/social-links.php?id='.$link['id']))?>">Редактировать</a>
              <form method="post" onsubmit="return confirm('Удалить эту ссылку?')">
                <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?=$link['id']?>">
                <button class="danger" type="submit">Удалить</button>
              </form>
            </div>
          </article>
        <?php endforeach;?>
      </div>
    <?php else:?>
      <div class="social-admin-empty">
        <div>
          <b>Социальные сети ещё не добавлены</b>
          <p>Добавьте VK, Одноклассники, MAX, Дзен, Rutube или другой сервис через форму слева.</p>
        </div>
      </div>
    <?php endif;?>
  </section>
</div>

<?php require __DIR__.'/_bottom.php'; ?>
