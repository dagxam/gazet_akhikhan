<?php require_admin(); $me=admin_user(); $maintenanceOn=maintenance_mode_enabled(); $adminTheme=admin_theme_name(); $adminLogo=branding_asset('admin_logo','assets/img/akhikhan-logo-transparent.webp'); $siteFavicon=branding_asset('site_favicon','assets/img/seal.svg'); ?><!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($adminTitle ?? 'Админ-панель')?> — АХИХЪАН</title><link rel="icon" href="<?=e(base_url($siteFavicon))?>"><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Manrope:wght@400;500;600;700;800&family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Noto+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Noto+Serif:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=PT+Serif:ital,wght@0,400;0,700;1,400;1,700&family=Rubik:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"><link rel="stylesheet" href="<?=e(base_url('assets/css/admin.css?v=20260929-rich-editor1'))?>"></head><body class="admin-theme-<?=e($adminTheme)?>">
<aside class="admin-side">
  <?php
    $adminPage=basename((string)($_SERVER['PHP_SELF']??''));
    $adminNavActive=function(array $pages) use ($adminPage): string {
      return in_array($adminPage,$pages,true)?' is-active':'';
    };
    $settingsGroupPages=['settings.php','right-block.php','main-menu.php','social-links.php'];
    $settingsGroupOpen=in_array($adminPage,$settingsGroupPages,true);
  ?>

  <a class="admin-brand" href="<?=e(base_url('admin/'))?>" aria-label="АХИХЪАН — редакционная панель">
    <span class="admin-brand-logo">
      <img src="<?=e(base_url($adminLogo))?>" alt="АХИХЪАН">
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
    <a class="admin-nav-link<?=$adminNavActive(['photo-gallery.php'])?>" href="<?=e(base_url('admin/photo-gallery.php'))?>">
      <span class="admin-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4z"></path><path d="M7 15l3-3 2 2 3-4 3 5"></path><circle cx="8" cy="9" r="1.2"></circle></svg></span>
      <span>Фотогалерея</span>
    </a>
    <a class="admin-nav-link<?=$adminNavActive(['video-gallery.php'])?>" href="<?=e(base_url('admin/video-gallery.php'))?>">
      <span class="admin-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="14" rx="2"></rect><path d="M10 9l5 3-5 3z"></path></svg></span>
      <span>Видеогалерея</span>
    </a>
    <a class="admin-nav-link<?=$adminNavActive(['static-pages.php'])?>" href="<?=e(base_url('admin/static-pages.php'))?>">
      <span class="admin-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l3 3v15H6z"></path><path d="M15 3v5h5M9 12h6M9 16h4"></path></svg></span>
      <span>Статичные страницы</span>
    </a>
    <details class="admin-nav-group <?=$settingsGroupOpen?'is-active':''?>" <?=$settingsGroupOpen?'open':''?>>
      <summary class="admin-nav-group-summary">
        <span class="admin-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8.5A3.5 3.5 0 1 0 12 15.5 3.5 3.5 0 0 0 12 8.5z"></path><path d="M19 13.5v-3l-2-.7-.5-1.2.9-1.9-2.1-2.1-1.9.9-1.2-.5-.7-2h-3l-.7 2-1.2.5-1.9-.9-2.1 2.1.9 1.9-.5 1.2-2 .7v3l2 .7.5 1.2-.9 1.9 2.1 2.1 1.9-.9 1.2.5.7 2h3l.7-2 1.2-.5 1.9.9 2.1-2.1-.9-1.9.5-1.2z"></path></svg></span>
        <span>Настройки</span>
        <i class="admin-nav-group-chevron">⌄</i>
      </summary>
      <div class="admin-nav-submenu">
        <a class="<?=$adminPage==='settings.php'?'is-active':''?>" href="<?=e(base_url('admin/settings.php'))?>">
          <i class="fa-solid fa-sliders"></i><span>Основные настройки</span>
        </a>
        <a class="<?=$adminPage==='right-block.php'?'is-active':''?>" href="<?=e(base_url('admin/right-block.php'))?>">
          <i class="fa-regular fa-rectangle-list"></i><span>Правые блоки</span>
        </a>
        <a class="<?=$adminPage==='main-menu.php'?'is-active':''?>" href="<?=e(base_url('admin/main-menu.php'))?>">
          <i class="fa-solid fa-bars"></i><span>Главное меню</span>
        </a>
        <a class="<?=$adminPage==='social-links.php'?'is-active':''?>" href="<?=e(base_url('admin/social-links.php'))?>">
          <i class="fa-solid fa-share-nodes"></i><span>Мы в соцсетях</span>
        </a>
      </div>
    </details>
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
