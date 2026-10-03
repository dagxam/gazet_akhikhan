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

$pending=$_SESSION['two_factor_pending']??null;
if(is_array($pending) && (time()-(int)($pending['created_at']??0))>300){
  unset($_SESSION['two_factor_pending']);
  $pending=null;
}

$completeLogin=function(array $u): void {
  $email=(string)($u['email']??'');
  unset($u['password_hash'],$u['two_factor_secret'],$u['two_factor_recovery_codes'],$u['two_factor_enabled'],$u['two_factor_confirmed_at']);
  unset($_SESSION['two_factor_pending']);
  session_regenerate_id(true);
  $_SESSION['admin_user']=$u;
  $_SESSION['admin_login_at']=time();
  $_SESSION['admin_last_activity']=time();
  $_SESSION['admin_last_regen']=time();
  login_rate_limit_clear($email);
  security_log_event('login-success',[
    'user_id'=>(int)$u['id'],
    'role'=>(string)($u['role']??''),
    'two_factor'=>1,
  ]);
  header('Location: '.base_url('admin/'));
  exit;
};

if($_SERVER['REQUEST_METHOD']==='POST'){
  verify_csrf();
  $action=(string)($_POST['action']??'password');

  if($action==='two_factor' && is_array($pending)){
    $userId=(int)($pending['user_id']??0);
    $code=trim((string)($_POST['two_factor_code']??''));
    $rate=security_rate_limit('login-2fa',security_client_ip().'|'.$userId,8,5*60,10*60,false);

    if(!empty($rate['blocked'])){
      http_response_code(429);
      header('Retry-After: '.max(60,(int)$rate['remaining']));
      $error='Слишком много неверных кодов. Повторите позже.';
    }else{
      $q=db()->prepare("SELECT id,name,email,password_hash,role,two_factor_secret,two_factor_enabled,two_factor_recovery_codes,two_factor_confirmed_at FROM users WHERE id=? AND status='active' LIMIT 1");
      $q->execute([$userId]);
      $u=$q->fetch();

      if($u && !empty($u['two_factor_enabled']) && two_factor_verify_user($u,$code)){
        $completeLogin($u);
      }

      $rate=security_rate_limit('login-2fa',security_client_ip().'|'.$userId,8,5*60,10*60,true);
      security_log_event('login-2fa-failed',['user_id'=>$userId,'count'=>(int)($rate['count']??0)]);
      usleep(random_int(200000,350000));
      $error='Неверный код подтверждения.';
    }
  }elseif($action==='cancel_two_factor'){
    unset($_SESSION['two_factor_pending']);
    session_regenerate_id(true);
    header('Location: '.base_url('admin/login.php'));
    exit;
  }else{
    $email=trim($_POST['email']??'');
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
      $q=db()->prepare("SELECT id,name,email,password_hash,role,two_factor_secret,two_factor_enabled,two_factor_recovery_codes,two_factor_confirmed_at FROM users WHERE email=? AND status='active' LIMIT 1");
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

        if(!empty($u['two_factor_enabled'])){
          session_regenerate_id(true);
          $_SESSION['two_factor_pending']=[
            'user_id'=>(int)$u['id'],
            'email'=>(string)$u['email'],
            'created_at'=>time(),
          ];
          $pending=$_SESSION['two_factor_pending'];
          security_log_event('login-2fa-required',['user_id'=>(int)$u['id']]);
        }else{
          // Accounts without configured 2FA retain password-only access until
          // the user enables TOTP in Profile. No account is locked out by deployment.
          unset($u['password_hash'],$u['two_factor_secret'],$u['two_factor_recovery_codes'],$u['two_factor_enabled'],$u['two_factor_confirmed_at']);
          session_regenerate_id(true);
          $_SESSION['admin_user']=$u;
          $_SESSION['admin_login_at']=time();
          $_SESSION['admin_last_activity']=time();
          $_SESSION['admin_last_regen']=time();
          login_rate_limit_clear($email);
          security_log_event('login-success',[
            'user_id'=>(int)$u['id'],
            'role'=>(string)($u['role']??''),
            'two_factor'=>0,
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
<title>Вход в редакцию — АХИХЪАН</title>
<link rel="icon" href="<?=e(base_url($siteFavicon))?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?=e(base_url('assets/css/admin.css?v=20261002-security2'))?>">
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
        <h1>Вход в редакцию</h1>
        <p>Введите данные вашей учётной записи.</p>
      </div>

      <?php if(isset($_GET['installed'])):?><div class="ok">Сайт установлен. Войдите в админ-панель.</div><?php endif;?>
      <?php if($error):?><div class="error"><?=e($error)?></div><?php endif;?>

      <?php if(is_array($pending)):?>
      <form class="login-form" method="post" autocomplete="off">
        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
        <input type="hidden" name="action" value="two_factor">
        <div class="login-heading">
          <span>Двухфакторная защита</span>
          <p>Введите 6-значный код из приложения-аутентификатора или один резервный код.</p>
        </div>
        <label class="login-field">
          <span>Код подтверждения</span>
          <span class="login-input-wrap">
            <input type="text" name="two_factor_code" required autofocus inputmode="numeric" autocomplete="one-time-code" maxlength="16" placeholder="000000">
          </span>
        </label>
        <button class="login-submit" type="submit"><span>Подтвердить вход</span><b>→</b></button>
      </form>
      <form method="post" class="login-secondary-form">
        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
        <input type="hidden" name="action" value="cancel_two_factor">
        <button class="back" type="submit">← Ввести пароль заново</button>
      </form>
      <?php else:?>
      <form class="login-form" method="post">
        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
        <input type="hidden" name="action" value="password">

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
          <span>Войти</span>
          <b>→</b>
        </button>
        <p class="login-privacy-note">Для входа используются e-mail и пароль сотрудника. После включения 2FA потребуется одноразовый код. Информация об обработке данных — в <a href="<?=e(base_url('privacy.php'))?>" target="_blank" rel="noopener">Политике персональных данных</a>.</p>
      </form>
      <?php endif;?>

      <div class="login-card-footer">
        <a class="back" href="<?=e(base_url())?>">← Вернуться на сайт</a>
        <span>АХИХЪАН · Унцукульский район</span>
      </div>
    </section>
  </main>
</body>
</html>
