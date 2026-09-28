<?php
require dirname(__DIR__) . '/app/bootstrap.php';
if (!APP_INSTALLED) { header('Location: ../install.php'); exit; }
if(admin_user()){ header('Location: ' . base_url('admin/')); exit; }
$error='';
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
?><!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Вход — AKHIKHAN</title><link rel="stylesheet" href="<?=e(base_url('assets/css/admin.css'))?>"></head><body class="login-page"><form class="login-card" method="post"><div class="login-mark">AKHIKHAN.RU<span>Редакционная система</span></div><?php if(isset($_GET['installed'])):?><div class="ok">Сайт установлен. Войдите в админ-панель.</div><?php endif;?><?php if($error):?><div class="error"><?=e($error)?></div><?php endif;?><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><label>E-mail<input type="email" name="email" required autofocus></label><label>Пароль<input type="password" name="password" required></label><button>Войти</button><a class="back" href="<?=e(base_url())?>">← На сайт</a></form></body></html>
