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

        if($action==='two_factor_begin'){
            $secret=totp_generate_secret();
            $_SESSION['two_factor_setup']=[
                'user_id'=>(int)$me['id'],
                'secret'=>$secret,
                'created_at'=>time(),
            ];
            header('Location: '.base_url('admin/profile.php?two_factor_setup=1'));
            exit;
        }

        if($action==='two_factor_cancel'){
            unset($_SESSION['two_factor_setup']);
            header('Location: '.base_url('admin/profile.php'));
            exit;
        }

        if($action==='two_factor_enable'){
            $setup=$_SESSION['two_factor_setup']??null;
            if(!is_array($setup) || (int)($setup['user_id']??0)!==(int)$me['id'] || (time()-(int)($setup['created_at']??0))>600){
                unset($_SESSION['two_factor_setup']);
                throw new RuntimeException('Настройка 2FA истекла. Начните подключение заново.');
            }

            $secret=(string)($setup['secret']??'');
            $code=trim((string)($_POST['two_factor_code']??''));
            if($secret==='' || !totp_verify($secret,$code,1)){
                throw new RuntimeException('Код не подтверждён. Проверьте время на телефоне и попробуйте ещё раз.');
            }

            $recoveryCodes=two_factor_recovery_codes();
            db()->prepare('UPDATE users SET two_factor_secret=?,two_factor_enabled=1,two_factor_recovery_codes=?,two_factor_confirmed_at=CURRENT_TIMESTAMP WHERE id=?')
                ->execute([security_encrypt_secret($secret),two_factor_hash_recovery_codes($recoveryCodes),(int)$me['id']]);

            unset($_SESSION['two_factor_setup']);
            $_SESSION['two_factor_recovery_plain']=$recoveryCodes;
            security_log_event('two-factor-enabled',['user_id'=>(int)$me['id']]);
            header('Location: '.base_url('admin/profile.php?two_factor_enabled=1'));
            exit;
        }

        if($action==='two_factor_disable'){
            $password=(string)($_POST['current_password']??'');
            $code=trim((string)($_POST['two_factor_code']??''));
            $q=db()->prepare('SELECT id,password_hash,two_factor_secret,two_factor_enabled,two_factor_recovery_codes FROM users WHERE id=? LIMIT 1');
            $q->execute([(int)$me['id']]);
            $securityUser=$q->fetch();
            if(!$securityUser || !password_verify($password,(string)$securityUser['password_hash'])){
                throw new RuntimeException('Текущий пароль указан неверно.');
            }
            if(!two_factor_verify_user($securityUser,$code)){
                throw new RuntimeException('Код 2FA или резервный код неверен.');
            }
            db()->prepare('UPDATE users SET two_factor_secret=NULL,two_factor_enabled=0,two_factor_recovery_codes=NULL,two_factor_confirmed_at=NULL WHERE id=?')
                ->execute([(int)$me['id']]);
            security_log_event('two-factor-disabled',['user_id'=>(int)$me['id']]);
            header('Location: '.base_url('admin/profile.php?two_factor_disabled=1'));
            exit;
        }

        if($action==='two_factor_regenerate'){
            $code=trim((string)($_POST['two_factor_code']??''));
            $q=db()->prepare('SELECT id,two_factor_secret,two_factor_enabled,two_factor_recovery_codes FROM users WHERE id=? LIMIT 1');
            $q->execute([(int)$me['id']]);
            $securityUser=$q->fetch();
            if(!$securityUser || empty($securityUser['two_factor_enabled']) || !two_factor_verify_user($securityUser,$code)){
                throw new RuntimeException('Подтвердите действие действующим кодом 2FA.');
            }
            $recoveryCodes=two_factor_recovery_codes();
            db()->prepare('UPDATE users SET two_factor_recovery_codes=? WHERE id=?')
                ->execute([two_factor_hash_recovery_codes($recoveryCodes),(int)$me['id']]);
            $_SESSION['two_factor_recovery_plain']=$recoveryCodes;
            security_log_event('two-factor-recovery-regenerated',['user_id'=>(int)$me['id']]);
            header('Location: '.base_url('admin/profile.php?two_factor_recovery=1'));
            exit;
        }

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
                if(strlen($password)<12 || strlen($password)>72) throw new RuntimeException('Новый пароль должен содержать от 12 до 72 байт.');
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
            $editorPermissions=$role==='editor' ? posted_editor_permissions('new_editor_permissions') : [];
            if($role==='editor' && !$editorPermissions){
                throw new RuntimeException('Выберите хотя бы один раздел для редактора.');
            }

            if($name==='') throw new RuntimeException('Введите имя нового пользователя.');
            if(!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Введите корректный e-mail нового пользователя.');
            if(strlen($password)<12 || strlen($password)>72) throw new RuntimeException('Пароль нового пользователя должен содержать от 12 до 72 байт.');
            if(empty($_POST['basis_confirmed'])) throw new RuntimeException('Подтвердите наличие основания для создания учётной записи и уведомление сотрудника об обработке его данных.');

            $q=db()->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
            $q->execute([$email]);
            if($q->fetchColumn()!==false) throw new RuntimeException('Пользователь с таким e-mail уже существует.');

            $q=db()->prepare("INSERT INTO users(name,email,password_hash,role,status,editor_permissions) VALUES(?,?,?,?,'active',?)");
            $q->execute([
                $name,
                $email,
                password_hash($password,PASSWORD_DEFAULT),
                $role,
                $role==='editor' ? editor_permissions_json($editorPermissions) : null,
            ]);
            security_log_event('editor-access-created',[
                'created_user_id'=>(int)db()->lastInsertId(),
                'role'=>$role,
                'permissions'=>$role==='editor' ? $editorPermissions : ['all'],
            ]);

            header('Location: '.base_url('admin/profile.php?user_added=1'));
            exit;
        }

        if($action==='delete_user'){
            require_site_admin();

            $userId=(int)($_POST['user_id']??0);
            if($userId<=0) throw new RuntimeException('Пользователь не найден.');
            if($userId===(int)$me['id']) throw new RuntimeException('Нельзя удалить собственную учётную запись.');

            $q=db()->prepare('SELECT id,name,email,role,status FROM users WHERE id=? LIMIT 1');
            $q->execute([$userId]);
            $target=$q->fetch();
            if(!$target) throw new RuntimeException('Пользователь не найден.');

            if(
                ($target['role']??'')==='admin'
                && ($target['status']??'')==='active'
                && active_admin_count_excluding($userId)<1
            ){
                throw new RuntimeException('Нельзя удалить последнего активного администратора.');
            }

            db()->prepare('DELETE FROM users WHERE id=?')->execute([$userId]);
            security_log_event('editor-account-deleted',[
                'target_user_id'=>$userId,
                'target_role'=>(string)($target['role']??''),
            ]);

            header('Location: '.base_url('admin/profile.php?user_deleted=1'));
            exit;
        }

        if($action==='update_user'){
            require_site_admin();

            $userId=(int)($_POST['user_id']??0);
            $role=in_array($_POST['user_role']??'editor',['admin','editor'],true)?$_POST['user_role']:'editor';
            $status=in_array($_POST['user_status']??'active',['active','blocked'],true)?$_POST['user_status']:'active';
            $editorPermissions=$role==='editor' ? posted_editor_permissions('user_editor_permissions') : [];
            if($role==='editor' && !$editorPermissions){
                throw new RuntimeException('Для редактора выберите хотя бы один раздел управления.');
            }

            $q=db()->prepare('SELECT id,role,status,editor_permissions FROM users WHERE id=? LIMIT 1');
            $q->execute([$userId]);
            $target=$q->fetch();
            if(!$target) throw new RuntimeException('Пользователь не найден.');

            $removesActiveAdmin=($target['role']==='admin' && $target['status']==='active')
                && ($role!=='admin' || $status!=='active');
            if($removesActiveAdmin && active_admin_count_excluding($userId)<1){
                throw new RuntimeException('Нельзя отключить или понизить последнего активного администратора.');
            }

            db()->prepare('UPDATE users SET role=?,status=?,editor_permissions=? WHERE id=?')
                ->execute([
                    $role,
                    $status,
                    $role==='editor' ? editor_permissions_json($editorPermissions) : null,
                    $userId,
                ]);
            security_log_event('editor-access-updated',[
                'target_user_id'=>$userId,
                'role'=>$role,
                'status'=>$status,
                'permissions'=>$role==='editor' ? $editorPermissions : ['all'],
            ]);

            if($userId===(int)$me['id']){
                if($status!=='active'){
                    unset($_SESSION['admin_user']);
                    header('Location: '.base_url('admin/login.php'));
                    exit;
                }
                $_SESSION['admin_user']['role']=$role;
                $_SESSION['admin_user']['editor_permissions']=$role==='editor'
                    ? editor_permissions_json($editorPermissions)
                    : null;
            }

            header('Location: '.base_url('admin/profile.php?users_saved=1'));
            exit;
        }

        throw new RuntimeException('Неизвестное действие.');
    }catch(Throwable $e){
        $error=$e->getMessage();
    }
}

$q=db()->prepare('SELECT id,name,email,role,status,editor_permissions,two_factor_enabled,two_factor_confirmed_at,created_at FROM users WHERE id=? LIMIT 1');
$q->execute([(int)$me['id']]);
$current=$q->fetch() ?: $me;
$users=is_site_admin()
    ? db()->query("SELECT id,name,email,role,status,editor_permissions,created_at FROM users ORDER BY CASE WHEN role='admin' THEN 0 ELSE 1 END,name")->fetchAll()
    : [];
$usersPager=admin_paginate_array($users,10,'users_page');
$users=$usersPager['items'];
$editorPermissionCatalog=editor_permission_catalog();
$editorPermissionMeta=[
    'news'=>['icon'=>'fa-solid fa-newspaper','description'=>'Новости, публикации и рубрики'],
    'photos'=>['icon'=>'fa-regular fa-images','description'=>'Фотоальбомы и фотографии'],
    'videos'=>['icon'=>'fa-solid fa-video','description'=>'Видео и обложки'],
    'newspapers'=>['icon'=>'fa-solid fa-book-open','description'=>'PDF-выпуски газеты'],
    'documents'=>['icon'=>'fa-regular fa-file-lines','description'=>'Документы и файлы'],
];

$adminTitle='Профиль';
require __DIR__.'/_top.php';
?>

<?php if($error):?><div class="error"><?=e($error)?></div><?php endif;?>
<?php if(isset($_GET['saved'])):?><div class="ok">Профиль обновлён.</div><?php endif;?>
<?php if(isset($_GET['user_added'])):?><div class="ok">Новый пользователь добавлен.</div><?php endif;?>
<?php if(isset($_GET['users_saved'])):?><div class="ok">Права пользователя обновлены.</div><?php endif;?>
<?php if(isset($_GET['user_deleted'])):?><div class="ok">Сотрудник удалён. Его опубликованные материалы сохранены.</div><?php endif;?>
<?php if(isset($_GET['maintenance_saved'])):?><div class="ok">Режим реконструкции обновлён.</div><?php endif;?>
<?php if(isset($_GET['two_factor_enabled'])):?><div class="ok">Двухфакторная защита включена.</div><?php endif;?>
<?php if(isset($_GET['two_factor_disabled'])):?><div class="ok">Двухфакторная защита отключена.</div><?php endif;?>
<?php if(isset($_GET['two_factor_recovery'])):?><div class="ok">Резервные коды обновлены. Старые коды больше не действуют.</div><?php endif;?>
<?php
$twoFactorSetup=$_SESSION['two_factor_setup']??null;
if(is_array($twoFactorSetup) && ((int)($twoFactorSetup['user_id']??0)!==(int)$me['id'] || (time()-(int)($twoFactorSetup['created_at']??0))>600)){
    unset($_SESSION['two_factor_setup']);
    $twoFactorSetup=null;
}
$recoveryPlain=$_SESSION['two_factor_recovery_plain']??[];
unset($_SESSION['two_factor_recovery_plain']);
?>

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
          <input type="password" name="password" minlength="12" maxlength="72" autocomplete="new-password" placeholder="Оставьте пустым, если не меняете">
        </label>
        <label class="field-modern compact">
          <span>Повторите пароль</span>
          <input type="password" name="password_confirm" minlength="12" maxlength="72" autocomplete="new-password">
        </label>
      </div>

      <p class="login-privacy-note">Имя и e-mail используются для работы учётной записи редакционной системы. Подробнее об обработке, хранении, уточнении и удалении данных — в <a href="<?=e(base_url('privacy.php'))?>" target="_blank" rel="noopener">Политике обработки персональных данных</a>.</p>

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

<section class="editor-card profile-security-card">
  <div class="users-management-head">
    <div>
      <span class="editor-eyebrow">Безопасность входа</span>
      <h2>Двухфакторная защита</h2>
      <p>Одноразовые TOTP-коды совместимы с Google Authenticator, Microsoft Authenticator, 1Password и другими приложениями.</p>
    </div>
    <span class="profile-role-badge <?=!empty($current['two_factor_enabled'])?'admin':'editor'?>"><?=!empty($current['two_factor_enabled'])?'2FA включена':'2FA выключена'?></span>
  </div>

  <?php if($recoveryPlain):?>
    <div class="ok">
      <strong>Сохраните резервные коды сейчас.</strong> Каждый код работает только один раз, повторно они не показываются.
      <div class="two-factor-recovery-grid">
        <?php foreach($recoveryPlain as $recoveryCode):?><code><?=e($recoveryCode)?></code><?php endforeach;?>
      </div>
    </div>
  <?php endif;?>

  <?php if(empty($current['two_factor_enabled'])):?>
    <?php if(is_array($twoFactorSetup)): 
      $setupSecret=(string)($twoFactorSetup['secret']??'');
      $setupUri=two_factor_otpauth_uri((string)$current['email'],$setupSecret);
    ?>
      <div class="two-factor-setup">
        <p><strong>1.</strong> Откройте приложение-аутентификатор и добавьте новую TOTP-учётную запись.</p>
        <p><strong>2.</strong> Введите секрет вручную:</p>
        <code class="two-factor-secret"><?=e($setupSecret)?></code>
        <p class="admin-intro">URI для приложений, которые поддерживают прямой импорт:</p>
        <a class="edit-action" href="<?=e($setupUri)?>">Открыть в приложении-аутентификатор</a>
        <form method="post" class="two-factor-confirm-form">
          <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
          <input type="hidden" name="action" value="two_factor_enable">
          <label class="field-modern compact">
            <span>6-значный код</span>
            <input name="two_factor_code" required inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder="000000">
          </label>
          <button class="primary" type="submit">Подтвердить и включить 2FA</button>
        </form>
        <form method="post">
          <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
          <input type="hidden" name="action" value="two_factor_cancel">
          <button class="secondary" type="submit">Отменить настройку</button>
        </form>
      </div>
    <?php else:?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
        <input type="hidden" name="action" value="two_factor_begin">
        <button class="primary" type="submit">Подключить приложение-аутентификатор</button>
      </form>
    <?php endif;?>
  <?php else:?>
    <p class="admin-intro">2FA подтверждена <?=e(ru_date($current['two_factor_confirmed_at']??''))?>. При каждом новом входе после пароля потребуется одноразовый код.</p>

    <div class="profile-password-grid">
      <form method="post">
        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
        <input type="hidden" name="action" value="two_factor_regenerate">
        <label class="field-modern compact">
          <span>Текущий код 2FA</span>
          <input name="two_factor_code" required inputmode="numeric" autocomplete="one-time-code" maxlength="16">
        </label>
        <button class="secondary" type="submit">Создать новые резервные коды</button>
      </form>

      <form method="post" data-confirm="Отключить двухфакторную защиту для этой учётной записи?">
        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
        <input type="hidden" name="action" value="two_factor_disable">
        <label class="field-modern compact">
          <span>Текущий пароль</span>
          <input type="password" name="current_password" required autocomplete="current-password">
        </label>
        <label class="field-modern compact">
          <span>Код 2FA или резервный код</span>
          <input name="two_factor_code" required autocomplete="one-time-code" maxlength="16">
        </label>
        <button class="danger" type="submit">Отключить 2FA</button>
      </form>
    </div>
  <?php endif;?>
</section>

<?php if(is_site_admin()):?>
<section class="editor-card users-management-card">
  <div class="users-management-head">
    <div>
      <span class="editor-eyebrow">Команда редакции</span>
      <h2>Пользователи</h2>
      <p>Администратор может добавлять сотрудников, назначать роль, выбирать доступные редактору разделы и временно блокировать доступ.</p>
    </div>
  </div>

  <div class="users-management-grid">
    <form method="post" class="new-user-form">
      <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
      <input type="hidden" name="action" value="add_user">
      <h3>Добавить пользователя</h3>

      <label class="field-modern compact"><span>Имя</span><input name="new_name" required maxlength="120"></label>
      <label class="field-modern compact"><span>E-mail</span><input type="email" name="new_email" required maxlength="190"></label>
      <label class="field-modern compact"><span>Пароль</span><input type="password" name="new_password" required minlength="12" maxlength="72" autocomplete="new-password"></label>
      <label class="field-modern compact">
        <span>Должность</span>
        <select name="new_role">
          <option value="editor">Редактор</option>
          <option value="admin">Администратор</option>
        </select>
      </label>

      <fieldset class="editor-permissions-box editor-permissions-create">
        <legend>Разделы управления редактора</legend>
        <p>Отметьте только те разделы, за которые сотрудник будет отвечать. Для администратора ограничения не применяются.</p>
        <div class="editor-permissions-grid">
          <?php foreach($editorPermissionCatalog as $permissionKey=>$permissionLabel):
            $permissionMeta=$editorPermissionMeta[$permissionKey]??['icon'=>'fa-solid fa-shield-halved','description'=>'Доступ к разделу'];
          ?>
            <label class="editor-permission-check">
              <input type="checkbox" name="new_editor_permissions[]" value="<?=e($permissionKey)?>">
              <span class="editor-permission-visual"><i class="<?=e($permissionMeta['icon'])?>" aria-hidden="true"></i></span>
              <span class="editor-permission-copy">
                <b><?=e($permissionLabel)?></b>
                <small><?=e($permissionMeta['description'])?></small>
              </span>
            </label>
          <?php endforeach;?>
        </div>
      </fieldset>

      <label class="admin-pd-confirm"><input type="checkbox" name="basis_confirmed" value="1" required><span>Подтверждаю наличие правового основания для создания учётной записи и уведомление сотрудника о <a href="<?=e(base_url('privacy.php'))?>" target="_blank" rel="noopener">Политике обработки персональных данных</a>. Это подтверждение администратора, а не согласие за другого человека.</span></label>
      <button class="primary wide" type="submit">Добавить сотрудника</button>
    </form>

    <div class="users-list">
      <?php foreach($users as $user):
        $assignedPermissions=$user['role']==='editor'
          ? normalize_editor_permissions($user['editor_permissions']??null,false)
          : [];
      ?>
        <article class="user-admin-row">
          <div class="user-admin-row-head">
            <div class="user-admin-identity">
              <span class="user-mini-avatar"><?=e(function_exists('mb_substr') ? mb_strtoupper(mb_substr($user['name'],0,1,'UTF-8'),'UTF-8') : strtoupper(substr($user['name'],0,1)))?></span>
              <div>
                <strong><?=e($user['name'])?> <?=((int)$user['id']===(int)$current['id'])?'<em>Вы</em>':''?></strong>
                <small><?=e($user['email'])?></small>
              </div>
            </div>
            <div class="user-admin-state">
              <span class="user-role-pill <?=e($user['role'])?>"><?=e(role_label($user['role']))?></span>
              <span class="user-status-pill <?=$user['status']==='active'?'is-active':'is-blocked'?>"><?=$user['status']==='active'?'Активен':'Заблокирован'?></span>
            </div>
          </div>

          <form method="post" class="user-access-form">
            <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
            <input type="hidden" name="action" value="update_user">
            <input type="hidden" name="user_id" value="<?=$user['id']?>">

            <div class="user-access-toolbar">
              <label class="user-access-control">
                <span>Роль</span>
                <select name="user_role" aria-label="Должность <?=e($user['name'])?>">
                  <option value="admin" <?=$user['role']==='admin'?'selected':''?>>Администратор</option>
                  <option value="editor" <?=$user['role']==='editor'?'selected':''?>>Редактор</option>
                </select>
              </label>
              <label class="user-access-control">
                <span>Статус</span>
                <select name="user_status" aria-label="Статус <?=e($user['name'])?>">
                  <option value="active" <?=$user['status']==='active'?'selected':''?>>Активен</option>
                  <option value="blocked" <?=$user['status']==='blocked'?'selected':''?>>Заблокирован</option>
                </select>
              </label>
              <button class="secondary user-access-save" type="submit">Сохранить права</button>
            </div>

            <fieldset class="editor-permissions-box editor-permissions-compact">
              <legend>Доступ к разделам</legend>
              <?php if($user['role']==='editor' && !$assignedPermissions):?>
                <div class="editor-no-access-note">
                  <i class="fa-solid fa-lock" aria-hidden="true"></i>
                  <span><b>Права не назначены</b><small>В левом меню редактора будут только «Обзор» и «Профиль», пока администратор не сохранит хотя бы один раздел.</small></span>
                </div>
              <?php endif;?>
              <?php if($user['role']==='admin'):?>
                <div class="editor-full-access-note">
                  <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                  <span><b>Полный доступ администратора</b><small>Галочки ниже используются только при переводе этого сотрудника в роль редактора.</small></span>
                </div>
              <?php endif;?>
              <div class="editor-permissions-grid">
                <?php foreach($editorPermissionCatalog as $permissionKey=>$permissionLabel):
                  $permissionMeta=$editorPermissionMeta[$permissionKey]??['icon'=>'fa-solid fa-shield-halved','description'=>'Доступ к разделу'];
                ?>
                  <label class="editor-permission-check">
                    <input
                      type="checkbox"
                      name="user_editor_permissions[]"
                      value="<?=e($permissionKey)?>"
                      <?=in_array($permissionKey,$assignedPermissions,true)?'checked':''?>
                    >
                    <span class="editor-permission-visual"><i class="<?=e($permissionMeta['icon'])?>" aria-hidden="true"></i></span>
                    <span class="editor-permission-copy">
                      <b><?=e($permissionLabel)?></b>
                      <small><?=e($permissionMeta['description'])?></small>
                    </span>
                  </label>
                <?php endforeach;?>
              </div>
            </fieldset>
          </form>

          <?php if((int)$user['id']!==(int)$current['id']):?>
            <div class="user-admin-danger-zone">
              <div class="user-admin-danger-copy">
                <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                <span><b>Удалить сотрудника</b><small>Учётная запись будет удалена, опубликованные материалы останутся на сайте.</small></span>
              </div>
              <form method="post" data-confirm="Удалить сотрудника <?=e($user['name'])?>? Это действие нельзя отменить.">
                <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" value="<?=$user['id']?>">
                <button class="danger user-delete-button" type="submit"><i class="fa-regular fa-trash-can" aria-hidden="true"></i><span>Удалить</span></button>
              </form>
            </div>
          <?php endif;?>
        </article>
      <?php endforeach;?>
      <?php render_admin_pagination('admin/profile.php',$usersPager['page'],$usersPager['total_pages'],[],'users_page','Страницы пользователей'); ?>
    </div>
  </div>
</section>
<?php endif;?>

<?php require __DIR__.'/_bottom.php'; ?>
