<?php
require dirname(__DIR__) . '/app/bootstrap.php';
if (!APP_INSTALLED) { header('Location: ../install.php'); exit; }
if(admin_user()){ header('Location: ' . base_url('admin/')); exit; }

header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow, noarchive');

$error='';
$adminTheme=admin_theme_name();
$adminLoginLogo=branding_asset('admin_logo','assets/img/akhikhan-logo-transparent.webp');
$siteFavicon=branding_asset('site_favicon','assets/img/seal.svg');

if(isset($_GET['reset'])){
  unset($_SESSION['pending_2fa'],$_SESSION['pending_2fa_setup'],$_SESSION['totp_setup_secret'],$_SESSION['totp_setup_user_id']);
}

$pending=$_SESSION['pending_2fa']??null;
if(is_array($pending) && (time()-(int)($pending['created_at']??0))>10*60){
  unset($_SESSION['pending_2fa']);
  $pending=null;
}

$stage=is_array($pending)?'totp':'password';

if($_SERVER['REQUEST_METHOD']==='POST'){
  verify_csrf();
  $postedStage=(string)($_POST['stage']??'password');

  if($postedStage==='totp'){
    $pending=$_SESSION['pending_2fa']??null;
    if(!is_array($pending) || empty($pending['user_id']) || (time()-(int)($pending['created_at']??0))>10*60){
      unset($_SESSION['pending_2fa']);
      $error='Сессия двухфакторной проверки истекла. Введите e-mail и пароль снова.';
      $stage='password';
    }else{
      $userId=(int)$pending['user_id'];
      $rate=security_rate_limit('login-2fa',security_client_ip().'|'.$userId,8,5*60,15*60,true);
      if(!empty($rate['blocked'])){
        http_response_code(429);
        header('Retry-After: '.max(60,(int)$rate['remaining']));
        $error='Слишком много попыток проверки 2FA. Повторите позже.';
        $stage='totp';
      }else{
        $q=db()->prepare("SELECT id,name,email,password_hash,role,status,totp_secret,totp_enabled_at,totp_recovery_codes FROM users WHERE id=? AND status='active' LIMIT 1");
        $q->execute([$userId]);
        $u=$q->fetch();
        $code=trim((string)($_POST['totp_code']??''));

        if($u && user_totp_enabled($u) && verify_user_totp_or_recovery($u,$code)){
          establish_admin_session($u);
          login_rate_limit_clear((string)$u['email']);
          security_log_event('login-2fa-success',[
            'user_id'=>(int)$u['id'],
            'role'=>(string)$u['role'],
          ]);
          header('Location: '.base_url('admin/'));
          exit;
        }

        security_log_event('login-2fa-failed',['user_id'=>$userId]);
        usleep(random_int(180000,320000));
        $error='Неверный код приложения-аутентификатора или резервный код.';
        $stage='totp';
      }
    }
  }else{
    $email=trim((string)($_POST['email']??''));
    $pass=(string)($_POST['password']??'');

    $limitState=login_rate_limit_status($email);
    if(!empty($limitState['blocked'])){
      $retry=max(60,(int)$limitState['remaining']);
      security_log_event('login-blocked',[
        'email_hash'=>hash('sha256',strtolower($email)),
        'retry_after'=>$retry,
      ]);
      http_response_code(429);
      header('Retry-After: '.$retry);
      $error='Слишком много попыток входа. Повторите позже.';
    }else{
      $q=db()->prepare("SELECT id,name,email,password_hash,role,status,totp_secret,totp_enabled_at,totp_recovery_codes FROM users WHERE email=? AND status='active' LIMIT 1");
      $q->execute([$email]);
      $u=$q->fetch();

      $passwordOk=false;
      if($u){
        $passwordOk=password_verify($pass,$u['password_hash']);
      }else{
        password_verify($pass,'$2y$10$wHh0w6mLVuVwRzOeYtW8GuS8h1U40DC4qD9QIFRMvQqLJZc2jta.G');
      }

      if($u && $passwordOk){
        if(password_needs_rehash($u['password_hash'],PASSWORD_DEFAULT)){
          $newHash=password_hash($pass,PASSWORD_DEFAULT);
          db()->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([$newHash,$u['id']]);
        }

        login_rate_limit_clear($email);
        session_regenerate_id(true);

        if(user_totp_enabled($u)){
          $_SESSION['pending_2fa']=[
            'user_id'=>(int)$u['id'],
            'created_at'=>time(),
          ];
          $stage='totp';
          $pending=$_SESSION['pending_2fa'];
        }elseif(security_2fa_required()){
          $_SESSION['pending_2fa_setup']=[
            'user_id'=>(int)$u['id'],
            'created_at'=>time(),
          ];
          unset($_SESSION['totp_setup_secret'],$_SESSION['totp_setup_user_id']);
          security_log_event('login-2fa-enrollment-required',['user_id'=>(int)$u['id']]);
          header('Location: '.base_url('admin/2fa-setup.php'));
          exit;
        }else{
          establish_admin_session($u);
          security_log_event('login-success',[
            'user_id'=>(int)$u['id'],
            'role'=>(string)$u['role'],
          ]);
          header('Location: '.base_url('admin/'));
          exit;
        }
      }else{
        $limitState=login_rate_limit_failure($email);
        security_log_event('login-failed',[
          'email_hash'=>hash('sha256',strtolower($email)),
          'count'=>(int)($limitState['count']??0),
          'blocked'=>!empty($limitState['blocked']),
        ]);
        usleep(random_int(250000,450000));
        if(!empty($limitState['blocked'])){
          http_response_code(429);
          header('Retry-After: '.max(60,(int)$limitState['remaining']));
          $error='Слишком много попыток входа. Повторите позже.';
        }else{
          $error='Неверный e-mail или пароль.';
        }
      }
    }
  }
}
?><!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#efe8dd">
<title><?=$stage==='totp'?'Проверка 2FA':'Вход в редакцию'?> — АХИХЪАН</title>
<link rel="icon" href="<?=e(base_url($siteFavicon))?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?=e(base_url('assets/css/admin.css?v=20261003-security4'))?>">
</head>
<body class="login-page login-page-premium admin-theme-<?=e($adminTheme)?>">
  <div class="login-page-ornament login-page-ornament-left" aria-hidden="true"></div>
  <div class="login-page-ornament login-page-ornament-right" aria-hidden="true"></div>

  <main class="login-shell">
    <section class="login-card login-card-premium">
      <a class="login-brand" href="<?=e(base_url())?>" aria-label="АХИХЪАН — на сайт">
        <img src="<?=e(base_url($adminLoginLogo))?>" alt="АХИХЪАН — редакционная система">
      </a>

      <div class="login-divider" aria-hidden="true"><span></span><i></i><span></span></div>

      <div class="login-heading">
        <span>Редакционная система</span>
        <?php if($stage==='totp'):?>
          <h1>Подтверждение входа</h1>
          <p>Введите шестизначный код из приложения-аутентификатора. При необходимости можно использовать один резервный код.</p>
        <?php else:?>
          <h1>Вход в редакцию</h1>
          <p>Введите данные вашей учётной записи.</p>
        <?php endif;?>
      </div>

      <?php if(isset($_GET['installed'])):?><div class="ok">Сайт установлен. Войдите в админ-панель.</div><?php endif;?>
      <?php if($error):?><div class="error"><?=e($error)?></div><?php endif;?>

      <?php if($stage==='totp'):?>
        <form class="login-form" method="post">
          <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
          <input type="hidden" name="stage" value="totp">

          <label class="login-field">
            <span>Код 2FA или резервный код</span>
            <span class="login-input-wrap">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l7 3v5c0 4.8-2.9 8.1-7 10-4.1-1.9-7-5.2-7-10V6z"></path><path d="M9 12l2 2 4-4"></path></svg>
              <input name="totp_code" required autofocus autocomplete="one-time-code" maxlength="20" placeholder="123456">
            </span>
          </label>

          <button class="login-submit" type="submit">
            <span>Подтвердить вход</span>
            <b>→</b>
          </button>
          <p class="login-privacy-note">Коды проверяются локально на сервере и не передаются сторонним сервисам.</p>
        </form>
        <div class="login-card-footer">
          <a class="back" href="<?=e(base_url('admin/login.php?reset=1'))?>">← Войти под другой учётной записью</a>
          <span>АХИХЪАН · 2FA</span>
        </div>
      <?php else:?>
        <form class="login-form" method="post">
          <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
          <input type="hidden" name="stage" value="password">

          <label class="login-field">
            <span>E-mail</span>
            <span class="login-input-wrap">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16v12H4z"></path><path d="M4 7l8 6 8-6"></path></svg>
              <input type="email" name="email" required autofocus autocomplete="username" placeholder="name@example.ru">
            </span>
          </label>

          <label class="login-field">
            <span>Пароль</span>
            <span class="login-input-wrap">
              <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
              <input type="password" name="password" required autocomplete="current-password" placeholder="Введите пароль">
            </span>
          </label>

          <button class="login-submit" type="submit">
            <span>Продолжить</span>
            <b>→</b>
          </button>
          <p class="login-privacy-note">После пароля редакционная система использует второй фактор входа. Информация об обработке данных — в <a href="<?=e(base_url('privacy.php'))?>" target="_blank" rel="noopener">Политике персональных данных</a>.</p>
        </form>

        <div class="login-card-footer">
          <a class="back" href="<?=e(base_url())?>">← Вернуться на сайт</a>
          <span>АХИХЪАН · Унцукульский район</span>
        </div>
      <?php endif;?>
    </section>
  </main>
</body>
</html>
