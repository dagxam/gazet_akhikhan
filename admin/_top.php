<?php require_admin(); $me=admin_user(); $maintenanceOn=maintenance_mode_enabled(); ?><!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($adminTitle ?? 'Админ-панель')?> — АХИХЪАН</title><link rel="stylesheet" href="<?=e(base_url('assets/css/admin.css?v=20260928-profile1'))?>"></head><body>
<aside class="admin-side">
  <a class="admin-logo" href="<?=e(base_url('admin/'))?>">АХИХЪАН<span>редакция</span></a>
  <nav>
    <a href="<?=e(base_url('admin/'))?>">Обзор</a>
    <a href="<?=e(base_url('admin/articles.php'))?>">Новости</a>
    <a href="<?=e(base_url('admin/categories.php'))?>">Рубрики</a>
    <a href="<?=e(base_url('admin/newspapers.php'))?>">Газета</a>
    <a href="<?=e(base_url('admin/documents.php'))?>">Документы</a>
    <a href="<?=e(base_url('admin/main-menu.php'))?>">Главное меню</a>
    <a href="<?=e(base_url('admin/settings.php'))?>">Настройки</a>
    <a href="<?=e(base_url())?>" target="_blank">Открыть сайт ↗</a>
  </nav>

  <div class="admin-side-bottom">
    <?php if(is_site_admin()):?>
      <form class="maintenance-switch <?=$maintenanceOn?'is-on':''?>" method="post" action="<?=e(base_url('admin/maintenance-toggle.php'))?>">
        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
        <input type="hidden" name="return" value="<?=e(basename((string)($_SERVER['PHP_SELF']??'index.php')))?>">
        <input type="hidden" name="maintenance_mode" value="0">
        <label>
          <span class="maintenance-switch-copy">
            <b>Сайт на реконструкции</b>
            <small><?=$maintenanceOn?'Посетителям закрыт':'Сайт открыт'?></small>
          </span>
          <span class="maintenance-switch-control">
            <input type="checkbox" name="maintenance_mode" value="1" <?=$maintenanceOn?'checked':''?> onchange="this.form.submit()">
            <i></i>
          </span>
        </label>
      </form>
    <?php endif;?>

    <a class="admin-profile-link" href="<?=e(base_url('admin/profile.php'))?>">
      <span class="admin-profile-avatar"><?=e(function_exists('mb_substr') ? mb_strtoupper(mb_substr($me['name'],0,1,'UTF-8'),'UTF-8') : strtoupper(substr($me['name'],0,1)))?></span>
      <span><b>Профиль</b><small><?=e(role_label($me['role']))?></small></span>
      <i>›</i>
    </a>

    <div class="admin-user">
      <span><?=e($me['name'])?></span>
      <a href="<?=e(base_url('admin/logout.php'))?>">Выйти</a>
    </div>
  </div>
</aside>
<div class="admin-main"><header class="admin-top"><button class="menu-toggle">☰</button><div class="admin-top-copy"><span>Редакционная панель</span><h1><?=e($adminTitle ?? 'Админ-панель')?></h1></div></header><main class="admin-content">
