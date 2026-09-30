<?php
if (!defined('ROOT_PATH')) {
    require __DIR__ . '/app/bootstrap.php';
}
if (!maintenance_mode_enabled()) {
    header('Location: ' . base_url());
    exit;
}
?><!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#211914">
<meta name="robots" content="noindex,nofollow">
<title>Сайт на реконструкции — АХИХЪАН</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?=e(base_url('assets/css/style.css?v=20260928-maintenance1'))?>">
</head>
<body class="maintenance-page">
  <div class="maintenance-pattern maintenance-pattern-left" aria-hidden="true"></div>
  <div class="maintenance-pattern maintenance-pattern-right" aria-hidden="true"></div>

  <main class="maintenance-shell">
    <section class="maintenance-card">
      <div class="maintenance-logo">
        <img src="<?=e(base_url('assets/img/akhikhan-logo-transparent.webp?v=20260928-transparent-logo1'))?>" alt="АХИХЪАН">
      </div>

      <div class="maintenance-divider" aria-hidden="true"><span></span><i></i><span></span></div>

      <span class="maintenance-kicker">Сетевое издание Унцукульского района</span>
      <h1>Сайт на реконструкции</h1>
      <p>Мы обновляем сайт «АХИХЪАН», чтобы сделать его удобнее, быстрее и современнее. Скоро публикации снова будут доступны.</p>

      <div class="maintenance-status">
        <span class="maintenance-status-dot"></span>
        <div>
          <b>Редакция работает</b>
          <small>Сайт временно закрыт только для посетителей</small>
        </div>
      </div>

      <footer class="maintenance-footer">
        <span><?=e(current_date_ru())?></span>
        <a href="<?=e(base_url('privacy.php'))?>">Персональные данные</a>
        <a href="<?=e(base_url('cookies.php'))?>">Cookie</a>
        <a href="<?=e(base_url('admin/login.php'))?>">Вход для редакции →</a>
      </footer>
    </section>
  </main>
</body>
</html>
