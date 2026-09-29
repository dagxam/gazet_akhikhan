<?php
require dirname(__DIR__) . '/app/bootstrap.php';
if (!APP_INSTALLED) { header('Location: ../install.php'); exit; }
if(admin_user()){ header('Location: ' . base_url('admin/')); exit; }

$error='';
$adminTheme=admin_theme_name();
$adminLoginLogo=branding_asset('admin_logo','assets/img/akhikhan-logo-transparent.webp');
$siteFavicon=branding_asset('site_favicon','assets/img/seal.svg');
if($_SERVER['REQUEST_METHOD']==='POST'){
  verify_csrf();
  $email=trim($_POST['email']??'');
  $pass=(string)($_POST['password']??'');
  $q=db()->prepare("SELECT id,name,email,password_hash,role FROM users WHERE email=? AND status='active' LIMIT 1");
  $q->execute([$email]);
  $u=$q->fetch();
  if($u && password_verify($pass,$u['password_hash'])){
    unset($u['password_hash']);
    session_regenerate_id(true);
    $_SESSION['admin_user']=$u;
    header('Location: '.base_url('admin/'));
    exit;
  }
  $error='Неверный e-mail или пароль.';
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
<link rel="stylesheet" href="<?=e(base_url('assets/css/admin.css?v=20260929-branding1'))?>">
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

      <form class="login-form" method="post">
        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">

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
      </form>

      <div class="login-card-footer">
        <a class="back" href="<?=e(base_url())?>">← Вернуться на сайт</a>
        <span>АХИХЪАН · Унцукульский район</span>
      </div>
    </section>
  </main>
</body>
</html>
