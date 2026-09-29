<?php
$navCategories = categories();
$bySlug = [];
foreach ($navCategories as $cat) $bySlug[$cat['slug']] = $cat;
$mainMenuItems = main_menu_items();
$siteFavicon = branding_asset('site_favicon','assets/img/seal.svg');
$siteHeaderLogo = branding_asset('site_header_logo','assets/img/akhikhan-logo-transparent.webp');
$topbarRegion = setting('topbar_region_label','Унцукульский район');
$topbarVk = trim(setting('topbar_vk_url',''));
$topbarOk = trim(setting('topbar_ok_url',''));
$topbarEmail = trim(setting('topbar_email',''));

function nav_link_for_slug(string $slug, string $fallbackLabel): string {
    global $bySlug;
    if (isset($bySlug[$slug])) return category_url($bySlug[$slug]);
    return base_url('search.php?q=' . rawurlencode($fallbackLabel));
}
?><!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#231d18">
<link rel="icon" href="<?=e(base_url($siteFavicon))?>">
<title><?=e(($pageTitle ?? '') ? $pageTitle . ' — АХИХЪАН' : 'АХИХЪАН — сетевое издание Унцукульского района')?></title>
<meta name="description" content="<?=e($pageDescription ?? 'Сетевое издание Унцукульского района Республики Дагестан: новости, общество, культура, спорт, люди и история.')?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?=e(base_url('assets/css/style.css?v=20260929-topbar-fixed2'))?>">
</head>
<body>
<a id="top"></a>
<div class="site-shell">

<div class="site-topbar">
  <div class="wrap site-topbar-inner">
    <div class="site-topbar-left">
      <span class="topbar-region">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s6-5.3 6-11a6 6 0 1 0-12 0c0 5.7 6 11 6 11z"></path><circle cx="12" cy="10" r="2"></circle></svg>
        <span><?=e($topbarRegion)?></span>
      </span>

      <span class="topbar-separator" aria-hidden="true"></span>

      <span class="topbar-weather" data-site-weather title="Погода в Унцукуле">
        <svg class="topbar-weather-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="9" r="3"></circle><path d="M9 2v2M9 14v2M2 9h2M14 9h2M4 4l1.5 1.5M12.5 12.5L14 14"></path><path d="M8 18h9a3 3 0 0 0 0-6 5 5 0 0 0-9.2 1.8A2.2 2.2 0 0 0 8 18z"></path></svg>
        <span data-site-weather-text>Погода · …°</span>
      </span>
    </div>

    <div class="site-topbar-right">
      <div class="topbar-socials" aria-label="Социальные сети">
        <?php if($topbarVk!==''):?>
          <a href="<?=e($topbarVk)?>" target="_blank" rel="noopener" aria-label="ВКонтакте" title="ВКонтакте">
        <?php else:?>
          <span class="is-disabled" aria-label="ВКонтакте — ссылка не настроена" title="Добавьте ссылку VK в настройках">
        <?php endif;?>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7.5c.2 5.1 2.7 8.2 7.1 8.2h.4v-2.9c2.3.2 4 1.9 4.7 2.9H20c-.9-1.7-3-3.8-4.5-4.6 1.5-.9 3.4-3.2 4-4.8h-3.4c-.7 1.6-2.4 3.8-4.6 4V6.3H8.2c.5.7.8 1.6.8 2.6v3.6C6.8 12 5.2 9.5 4.7 7.5z"></path></svg>
        <?php if($topbarVk!==''):?></a><?php else:?></span><?php endif;?>

        <?php if($topbarOk!==''):?>
          <a href="<?=e($topbarOk)?>" target="_blank" rel="noopener" aria-label="Одноклассники" title="Одноклассники">
        <?php else:?>
          <span class="is-disabled" aria-label="Одноклассники — ссылка не настроена" title="Добавьте ссылку Одноклассников в настройках">
        <?php endif;?>
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="6.5" r="3"></circle><path d="M7.2 12.5c1.5 1 3.1 1.5 4.8 1.5s3.3-.5 4.8-1.5M12 14v6M9 17l3-3 3 3"></path></svg>
        <?php if($topbarOk!==''):?></a><?php else:?></span><?php endif;?>

        <?php if($topbarEmail!==''):?>
          <a href="mailto:<?=e($topbarEmail)?>" aria-label="Написать в редакцию" title="<?=e($topbarEmail)?>">
        <?php else:?>
          <span class="is-disabled" aria-label="Почта — адрес не настроен" title="Добавьте почту в настройках">
        <?php endif;?>
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="5.5" width="17" height="13" rx="2"></rect><path d="M4.5 7l7.5 5.5L19.5 7"></path></svg>
        <?php if($topbarEmail!==''):?></a><?php else:?></span><?php endif;?>
      </div>

      <span class="topbar-separator" aria-hidden="true"></span>

      <a class="topbar-admin-login" href="<?=e(base_url('admin/login.php'))?>">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3"></circle><path d="M5.5 20c.5-4 3-6 6.5-6s6 2 6.5 6"></path></svg>
        <span>Вход в редакцию</span>
      </a>
    </div>
  </div>
</div>

<header class="reference-header" data-reference-header>
  <div class="wrap reference-header-inner">
    <a class="reference-logo" href="<?=e(base_url())?>" aria-label="АХИХЪАН — главная">
      <img src="<?=e(base_url($siteHeaderLogo))?>" alt="АХИХЪАН — сетевое издание Унцукульского района">
    </a>

    <button class="menu-toggle reference-mobile-toggle" type="button" aria-expanded="false" aria-controls="site-menu">
      <span></span><span></span><span></span><b>Меню</b>
    </button>

    <div class="desktop-menu-shell reference-menu-shell" data-menu-shell>
      <nav class="site-menu reference-site-menu" id="site-menu" aria-label="Основная навигация">
        <?php foreach($mainMenuItems as $menuItem):?>
          <a href="<?=e(main_menu_url($menuItem['url']))?>" <?=$menuItem['open_new_tab']?'target="_blank" rel="noopener"':''?>><?=e($menuItem['label'])?></a>
        <?php endforeach;?>
      </nav>

      <button class="menu-overflow-toggle reference-overflow-toggle" type="button" data-menu-overflow-toggle aria-expanded="false" aria-controls="menu-overflow-panel" aria-label="Показать дополнительные пункты меню" hidden>
        <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M5.5 7.5l4.5 4.5 4.5-4.5"></path></svg>
      </button>

      <div class="menu-overflow-panel reference-overflow-panel" id="menu-overflow-panel" data-menu-overflow-panel></div>
    </div>
  </div>
</header>

<main>
