<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$me=admin_user();
$error='';
$success='';

function active_admin_count_excluding(int $userId): int
{
    $q=db()->prepare("SELECT COUNT(*) FROM users WHERE role='admin' AND status='active' AND id<>?");
    $q->execute([$userId]);
    return (int)$q->fetchColumn();
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();

    try{
        $action=(string)($_POST['action']??'profile');

        if($action==='profile'){
            $userId=(int)$me['id'];
            $name=trim($_POST['name']??'');
            $email=trim($_POST['email']??'');
            $password=(string)($_POST['password']??'');
            $passwordConfirm=(string)($_POST['password_confirm']??'');

            if($name==='') throw new RuntimeException('Введите имя сотрудника.');
            if(!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Введите корректный e-mail.');

            $q=db()->prepare('SELECT id FROM users WHERE email=? AND id<>? LIMIT 1');
            $q->execute([$email,$userId]);
            if($q->fetchColumn()!==false) throw new RuntimeException('Этот e-mail уже используется другим пользователем.');

            $role=$me['role'];
            if(is_site_admin()){
                $requestedRole=in_array($_POST['role']??'admin',['admin','editor'],true)?$_POST['role']:'admin';
                if($requestedRole==='editor' && $me['role']==='admin' && active_admin_count_excluding($userId)<1){
                    throw new RuntimeException('Нельзя изменить роль последнего активного администратора.');
                }
                $role=$requestedRole;
            }

            if($password!==''){
                if(strlen($password)<8) throw new RuntimeException('Новый пароль должен содержать минимум 8 символов.');
                if($password!==$passwordConfirm) throw new RuntimeException('Пароли не совпадают.');
                $hash=password_hash($password,PASSWORD_DEFAULT);
                $q=db()->prepare('UPDATE users SET name=?,email=?,role=?,password_hash=? WHERE id=?');
                $q->execute([$name,$email,$role,$hash,$userId]);
            }else{
                $q=db()->prepare('UPDATE users SET name=?,email=?,role=? WHERE id=?');
                $q->execute([$name,$email,$role,$userId]);
            }

            $_SESSION['admin_user']=[
                'id'=>$userId,
                'name'=>$name,
                'email'=>$email,
                'role'=>$role,
            ];

            header('Location: '.base_url('admin/profile.php?saved=1'));
            exit;
        }

        if($action==='add_user'){
            require_site_admin();

            $name=trim($_POST['new_name']??'');
            $email=trim($_POST['new_email']??'');
            $password=(string)($_POST['new_password']??'');
            $role=in_array($_POST['new_role']??'editor',['admin','editor'],true)?$_POST['new_role']:'editor';

            if($name==='') throw new RuntimeException('Введите имя нового пользователя.');
            if(!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Введите корректный e-mail нового пользователя.');
            if(strlen($password)<8) throw new RuntimeException('Пароль нового пользователя должен содержать минимум 8 символов.');

            $q=db()->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
            $q->execute([$email]);
            if($q->fetchColumn()!==false) throw new RuntimeException('Пользователь с таким e-mail уже существует.');

            $q=db()->prepare("INSERT INTO users(name,email,password_hash,role,status) VALUES(?,?,?,?,'active')");
            $q->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),$role]);

            header('Location: '.base_url('admin/profile.php?user_added=1'));
            exit;
        }

        if($action==='update_user'){
            require_site_admin();

            $userId=(int)($_POST['user_id']??0);
            $role=in_array($_POST['user_role']??'editor',['admin','editor'],true)?$_POST['user_role']:'editor';
            $status=in_array($_POST['user_status']??'active',['active','blocked'],true)?$_POST['user_status']:'active';

            $q=db()->prepare('SELECT id,role,status FROM users WHERE id=? LIMIT 1');
            $q->execute([$userId]);
            $target=$q->fetch();
            if(!$target) throw new RuntimeException('Пользователь не найден.');

            $removesActiveAdmin=($target['role']==='admin' && $target['status']==='active')
                && ($role!=='admin' || $status!=='active');
            if($removesActiveAdmin && active_admin_count_excluding($userId)<1){
                throw new RuntimeException('Нельзя отключить или понизить последнего активного администратора.');
            }

            db()->prepare('UPDATE users SET role=?,status=? WHERE id=?')->execute([$role,$status,$userId]);

            if($userId===(int)$me['id']){
                if($status!=='active'){
                    unset($_SESSION['admin_user']);
                    header('Location: '.base_url('admin/login.php'));
                    exit;
                }
                $_SESSION['admin_user']['role']=$role;
            }

            header('Location: '.base_url('admin/profile.php?users_saved=1'));
            exit;
        }

        throw new RuntimeException('Неизвестное действие.');
    }catch(Throwable $e){
        $error=$e->getMessage();
    }
}

$q=db()->prepare('SELECT id,name,email,role,status,created_at FROM users WHERE id=? LIMIT 1');
$q->execute([(int)$me['id']]);
$current=$q->fetch() ?: $me;
$users=is_site_admin()
    ? db()->query('SELECT id,name,email,role,status,created_at FROM users ORDER BY CASE WHEN role="admin" THEN 0 ELSE 1 END,name')->fetchAll()
    : [];

$adminTitle='Профиль';
require __DIR__.'/_top.php';
?>

<?php if($error):?><div class="error"><?=e($error)?></div><?php endif;?>
<?php if(isset($_GET['saved'])):?><div class="ok">Профиль обновлён.</div><?php endif;?>
<?php if(isset($_GET['user_added'])):?><div class="ok">Новый пользователь добавлен.</div><?php endif;?>
<?php if(isset($_GET['users_saved'])):?><div class="ok">Права пользователя обновлены.</div><?php endif;?>
<?php if(isset($_GET['maintenance_saved'])):?><div class="ok">Режим реконструкции обновлён.</div><?php endif;?>

<div class="profile-admin-head">
  <div>
    <span class="editor-eyebrow">Учётная запись</span>
    <h2>Профиль</h2>
    <p>Управляйте своими данными и доступом сотрудников к редакционной панели.</p>
  </div>
  <span class="profile-role-badge <?=e($current['role'])?>"><?=e(role_label($current['role']))?></span>
</div>

<div class="profile-layout">
  <section class="editor-card profile-card">
    <div class="side-card-title">
      <span class="side-icon">П</span>
      <div>
        <h3>Мои данные</h3>
        <p>Имя, e-mail, должность и пароль</p>
      </div>
    </div>

    <form method="post">
      <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
      <input type="hidden" name="action" value="profile">

      <label class="field-modern compact">
        <span>Имя</span>
        <input name="name" required maxlength="120" value="<?=e($current['name']??'')?>">
      </label>

      <label class="field-modern compact">
        <span>E-mail</span>
        <input type="email" name="email" required maxlength="190" value="<?=e($current['email']??'')?>">
      </label>

      <label class="field-modern compact">
        <span>Должность</span>
        <?php if(is_site_admin()):?>
          <select name="role">
            <option value="admin" <?=$current['role']==='admin'?'selected':''?>>Администратор</option>
            <option value="editor" <?=$current['role']==='editor'?'selected':''?>>Редактор</option>
          </select>
          <small>Последнего активного администратора нельзя понизить.</small>
        <?php else:?>
          <input value="<?=e(role_label($current['role']))?>" disabled>
          <small>Изменить должность может администратор.</small>
        <?php endif;?>
      </label>

      <div class="profile-password-grid">
        <label class="field-modern compact">
          <span>Новый пароль</span>
          <input type="password" name="password" minlength="8" autocomplete="new-password" placeholder="Оставьте пустым, если не меняете">
        </label>
        <label class="field-modern compact">
          <span>Повторите пароль</span>
          <input type="password" name="password_confirm" minlength="8" autocomplete="new-password">
        </label>
      </div>

      <button class="primary profile-save" type="submit">Сохранить профиль</button>
    </form>
  </section>

  <aside class="editor-card profile-summary-card">
    <span class="profile-avatar"><?=e(function_exists('mb_substr') ? mb_strtoupper(mb_substr($current['name'],0,1,'UTF-8'),'UTF-8') : strtoupper(substr($current['name'],0,1)))?></span>
    <h3><?=e($current['name'])?></h3>
    <p><?=e($current['email'])?></p>
    <span class="profile-role-badge <?=e($current['role'])?>"><?=e(role_label($current['role']))?></span>
    <div class="profile-summary-line">
      <span>Статус</span>
      <b>Активен</b>
    </div>
    <div class="profile-summary-line">
      <span>В системе с</span>
      <b><?=e(ru_date($current['created_at']??''))?></b>
    </div>
  </aside>
</div>

<?php if(is_site_admin()):?>
<section class="editor-card users-management-card">
  <div class="users-management-head">
    <div>
      <span class="editor-eyebrow">Команда редакции</span>
      <h2>Пользователи</h2>
      <p>Администратор может добавлять сотрудников, назначать роль и временно блокировать доступ.</p>
    </div>
  </div>

  <div class="users-management-grid">
    <form method="post" class="new-user-form">
      <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
      <input type="hidden" name="action" value="add_user">
      <h3>Добавить пользователя</h3>

      <label class="field-modern compact"><span>Имя</span><input name="new_name" required maxlength="120"></label>
      <label class="field-modern compact"><span>E-mail</span><input type="email" name="new_email" required maxlength="190"></label>
      <label class="field-modern compact"><span>Пароль</span><input type="password" name="new_password" required minlength="8" autocomplete="new-password"></label>
      <label class="field-modern compact">
        <span>Должность</span>
        <select name="new_role">
          <option value="editor">Редактор</option>
          <option value="admin">Администратор</option>
        </select>
      </label>

      <button class="primary wide" type="submit">Добавить сотрудника</button>
    </form>

    <div class="users-list">
      <?php foreach($users as $user):?>
        <article class="user-admin-row">
          <div class="user-admin-identity">
            <span class="user-mini-avatar"><?=e(function_exists('mb_substr') ? mb_strtoupper(mb_substr($user['name'],0,1,'UTF-8'),'UTF-8') : strtoupper(substr($user['name'],0,1)))?></span>
            <div>
              <strong><?=e($user['name'])?> <?=$user['id']===(int)$current['id']?'<em>Вы</em>':''?></strong>
              <small><?=e($user['email'])?></small>
            </div>
          </div>

          <form method="post" class="user-access-form">
            <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
            <input type="hidden" name="action" value="update_user">
            <input type="hidden" name="user_id" value="<?=$user['id']?>">

            <select name="user_role" aria-label="Должность <?=e($user['name'])?>">
              <option value="admin" <?=$user['role']==='admin'?'selected':''?>>Администратор</option>
              <option value="editor" <?=$user['role']==='editor'?'selected':''?>>Редактор</option>
            </select>
            <select name="user_status" aria-label="Статус <?=e($user['name'])?>">
              <option value="active" <?=$user['status']==='active'?'selected':''?>>Активен</option>
              <option value="blocked" <?=$user['status']==='blocked'?'selected':''?>>Заблокирован</option>
            </select>
            <button class="secondary" type="submit">Сохранить</button>
          </form>
        </article>
      <?php endforeach;?>
    </div>
  </div>
</section>
<?php endif;?>

<?php require __DIR__.'/_bottom.php'; ?>
