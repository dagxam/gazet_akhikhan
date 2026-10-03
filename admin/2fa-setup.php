<?php
require dirname(__DIR__) . '/app/bootstrap.php';
if (!APP_INSTALLED) { header('Location: ../install.php'); exit; }

header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow, noarchive');

$admin=admin_user();
$pending=$_SESSION['pending_2fa_setup']??null;
if(is_array($pending) && (time()-(int)($pending['created_at']??0))>10*60){
    unset($_SESSION['pending_2fa_setup']);
    $pending=null;
}

$userId=$admin ? (int)$admin['id'] : (int)($pending['user_id']??0);
if($userId<1){
    header('Location: '.base_url('admin/login.php'));
    exit;
}

$q=db()->prepare("SELECT id,name,email,password_hash,role,status,totp_secret,totp_enabled_at,totp_recovery_codes FROM users WHERE id=? AND status='active' LIMIT 1");
$q->execute([$userId]);
$user=$q->fetch();
if(!$user){
    unset($_SESSION['pending_2fa_setup']);
    header('Location: '.base_url('admin/login.php'));
    exit;
}

$adminTheme=admin_theme_name();
$adminLoginLogo=branding_asset('admin_logo','assets/img/akhikhan-logo-transparent.webp');
$siteFavicon=branding_asset('site_favicon','assets/img/seal.svg');
$error='';
$recoveryCodes=$_SESSION['recovery_codes_once']??[];
unset($_SESSION['recovery_codes_once']);

if(user_totp_enabled($user) && !$recoveryCodes){
    if(!$admin){
        unset($_SESSION['pending_2fa_setup']);
        header('Location: '.base_url('admin/login.php'));
        exit;
    }
    header('Location: '.base_url('admin/profile.php?twofa=enabled'));
    exit;
}

if(empty($_SESSION['totp_setup_secret']) || (int)($_SESSION['totp_setup_user_id']??0)!==$userId){
    $_SESSION['totp_setup_secret']=totp_generate_secret();
    $_SESSION['totp_setup_user_id']=$userId;
}
$secret=(string)$_SESSION['totp_setup_secret'];
$issuer='AKHIKHAN';
$label=$issuer.':'.(string)$user['email'];
$otpauth='otpauth://totp/'.rawurlencode($label).'?secret='.rawurlencode($secret).'&issuer='.rawurlencode($issuer).'&algorithm=SHA1&digits=6&period=30';

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();

    $rate=security_rate_limit('2fa-enroll',security_client_ip().'|'.$userId,8,10*60,15*60,true);
    if(!empty($rate['blocked'])){
        http_response_code(429);
        header('Retry-After: '.max(60,(int)$rate['remaining']));
        $error='Слишком много попыток настройки 2FA. Повторите позже.';
    }else{
        $code=trim((string)($_POST['totp_code']??''));
        if(!totp_verify_code($secret,$code,1)){
            $error='Код не совпал. Проверьте время на телефоне и введите новый шестизначный код.';
        }else{
            try{
                $encrypted=security_encrypt_totp_secret($secret);
                $codes=totp_recovery_codes_generate(8);
                $hashes=totp_recovery_codes_hash($codes);

                $q=db()->prepare('UPDATE users SET totp_secret=?,totp_enabled_at=CURRENT_TIMESTAMP,totp_recovery_codes=? WHERE id=?');
                $q->execute([$encrypted,$hashes,$userId]);

                security_log_event('2fa-enabled',['user_id'=>$userId]);

                if(!$admin){
                    establish_admin_session($user);
                }else{
                    unset($_SESSION['pending_2fa_setup']);
                }

                unset($_SESSION['totp_setup_secret'],$_SESSION['totp_setup_user_id']);
                $_SESSION['recovery_codes_once']=$codes;
                header('Location: '.base_url('admin/2fa-setup.php?enabled=1'));
                exit;
            }catch(Throwable $e){
                security_log_event('2fa-enable-error',['user_id'=>$userId]);
                $error='Не удалось безопасно включить 2FA. Проверьте поддержку OpenSSL/Sodium на сервере.';
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
<title>Двухфакторная защита — АХИХЪАН</title>
<link rel="icon" href="<?=e(base_url($siteFavicon))?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?=e(base_url('assets/css/admin.css?v=20261003-security4'))?>">
</head>
<body class="login-page login-page-premium admin-theme-<?=e($adminTheme)?>">
  <main class="login-shell twofa-setup-shell">
    <section class="login-card login-card-premium twofa-setup-card">
      <a class="login-brand" href="<?=e(base_url())?>" aria-label="АХИХЪАН — на сайт">
        <img src="<?=e(base_url($adminLoginLogo))?>" alt="АХИХЪАН — редакционная система">
      </a>

      <div class="login-divider" aria-hidden="true"><span></span><i></i><span></span></div>

      <?php if($recoveryCodes):?>
        <div class="login-heading">
          <span>Защита включена</span>
          <h1>Сохраните резервные коды</h1>
          <p>Каждый код работает только один раз. Храните их отдельно от телефона и не отправляйте в мессенджеры.</p>
        </div>
        <div class="twofa-recovery-grid">
          <?php foreach($recoveryCodes as $code):?><code><?=e($code)?></code><?php endforeach;?>
        </div>
        <div class="twofa-warning">После ухода с этой страницы эти коды больше не будут показаны в открытом виде.</div>
        <a class="login-submit twofa-primary-link" href="<?=e(base_url('admin/'))?>"><span>Перейти в админ-панель</span><b>→</b></a>
      <?php else:?>
        <div class="login-heading">
          <span>Обязательная защита входа</span>
          <h1>Настройте 2FA</h1>
          <p>Добавьте учётную запись в Google Authenticator, Microsoft Authenticator, 1Password, Bitwarden или другое TOTP-приложение.</p>
        </div>

        <?php if($error):?><div class="error"><?=e($error)?></div><?php endif;?>

        <div class="twofa-secret-card">
          <span>Секретный ключ</span>
          <code><?=e(implode(' ',str_split($secret,4)))?></code>
          <small>Тип: TOTP · 6 цифр · период 30 секунд · SHA-1</small>
        </div>

        <details class="twofa-uri-details">
          <summary>Показать URI для ручного импорта</summary>
          <code><?=e($otpauth)?></code>
        </details>

        <ol class="twofa-steps">
          <li>Откройте приложение-аутентификатор и выберите добавление ключа вручную.</li>
          <li>Введите ключ выше. Тип — «по времени» / TOTP.</li>
          <li>Введите появившийся шестизначный код для подтверждения.</li>
        </ol>

        <form class="login-form" method="post">
          <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
          <label class="login-field">
            <span>Код из приложения</span>
            <span class="login-input-wrap">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l7 3v5c0 4.8-2.9 8.1-7 10-4.1-1.9-7-5.2-7-10V6z"></path><path d="M9 12l2 2 4-4"></path></svg>
              <input name="totp_code" required autofocus inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder="123456">
            </span>
          </label>
          <button class="login-submit" type="submit"><span>Включить двухфакторную защиту</span><b>→</b></button>
        </form>

        <p class="login-privacy-note">Секрет 2FA хранится на сервере в зашифрованном виде. Проверка кода выполняется локально и не требует обращения к сторонним сервисам.</p>
      <?php endif;?>
    </section>
  </main>
</body>
</html>
