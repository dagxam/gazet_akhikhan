<?php require_admin(); $me=admin_user(); $maintenanceOn=maintenance_mode_enabled(); ?><!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($adminTitle ?? 'Админ-панель')?> — АХИХЪАН</title><link rel="stylesheet" href="<?=e(base_url('assets/css/admin.css?v=20260929-rightblock1'))?>"></head><body>
<aside class="admin-side">
  <?php
    $adminPage=basename((string)($_SERVER['PHP_SELF']??''));
    $adminNavActive=function(array $pages) use ($adminPage): string {
      return in_array($adminPage,$pages,true)?' is-active':'';
    };
  ?>

  <a class="admin-brand" href="<?=e(base_url('admin/'))?>" aria-label="АХИХЪАН — редакционная панель">
    <span class="admin-brand-logo">
      <img src="<?=e(base_url('assets/img/akhikhan-logo-transparent.webp?v=20260928-transparent-logo1'))?>" alt="АХИХЪАН">
    </span>
    <span class="admin-brand-caption">
      <b>Редакционная система</b>
      <small>Унцукульский район</small>
    </span>
  </a>

  <nav class="admin-nav">
    <a class="admin-nav-link<?=$adminNavActive(['index.php'])?>" href="<?=e(base_url('admin/'))?>">
      <span class="admin-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"></path></svg></span>
      <span>Обзор</span>
    </a>
    <a class="admin-nav-link<?=$adminNavActive(['articles.php','article-edit.php'])?>" href="<?=e(base_url('admin/articles.php'))?>">
      <span class="admin-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l3 3v15H6z"></path><path d="M15 3v4h4M9 11h6M9 15h6"></path></svg></span>
      <span>Новости</span>
    </a>
    <a class="admin-nav-link<?=$adminNavActive(['categories.php'])?>" href="<?=e(base_url('admin/categories.php'))?>">
      <span class="admin-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h6v5H4zM14 6h6v5h-6zM4 15h6v3H4zM14 15h6v3h-6z"></path></svg></span>
      <span>Рубрики</span>
    </a>
    <a class="admin-nav-link<?=$adminNavActive(['newspapers.php'])?>" href="<?=e(base_url('admin/newspapers.php'))?>">
      <span class="admin-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h12a2 2 0 0 1 2 2v14H7a2 2 0 0 1-2-2z"></path><path d="M8 8h7M8 12h7M8 16h4"></path></svg></span>
      <span>Газета</span>
    </a>
    <a class="admin-nav-link<?=$adminNavActive(['documents.php'])?>" href="<?=e(base_url('admin/documents.php'))?>">
      <span class="admin-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l4 4v14H7z"></path><path d="M14 3v5h5M10 12h5M10 16h5"></path></svg></span>
      <span>Документы</span>
    </a>
    <a class="admin-nav-link<?=$adminNavActive(['main-menu.php'])?>" href="<?=e(base_url('admin/main-menu.php'))?>">
      <span class="admin-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 7h14M5 12h14M5 17h14"></path></svg></span>
      <span>Главное меню</span>
    </a>
    <a class="admin-nav-link<?=$adminNavActive(['right-block.php'])?>" href="<?=e(base_url('admin/right-block.php'))?>">
      <span class="admin-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4z"></path><path d="M14 5v14M17 9h1M17 13h1"></path></svg></span>
      <span>Правый блок</span>
    </a>
    <a class="admin-nav-link<?=$adminNavActive(['settings.php'])?>" href="<?=e(base_url('admin/settings.php'))?>">
      <span class="admin-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8.5A3.5 3.5 0 1 0 12 15.5 3.5 3.5 0 0 0 12 8.5z"></path><path d="M19 13.5v-3l-2-.7-.5-1.2.9-1.9-2.1-2.1-1.9.9-1.2-.5-.7-2h-3l-.7 2-1.2.5-1.9-.9-2.1 2.1.9 1.9-.5 1.2-2 .7v3l2 .7.5 1.2-.9 1.9 2.1 2.1 1.9-.9 1.2.5.7 2h3l.7-2 1.2-.5 1.9.9 2.1-2.1-.9-1.9.5-1.2z"></path></svg></span>
      <span>Настройки</span>
    </a>
    <a class="admin-nav-link admin-nav-site" href="<?=e(base_url())?>" target="_blank" rel="noopener">
      <span class="admin-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 19h14V5h-6"></path><path d="M13 4h7v7M20 4l-9 9"></path></svg></span>
      <span>Открыть сайт</span>
      <b>↗</b>
    </a>
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

    <a class="admin-profile-link <?=$adminPage==='profile.php'?'is-active':''?>" href="<?=e(base_url('admin/profile.php'))?>">
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
