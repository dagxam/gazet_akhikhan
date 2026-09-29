<?php
$navCategories = categories();
$bySlug = [];
foreach ($navCategories as $cat) $bySlug[$cat['slug']] = $cat;
$mainMenuItems = main_menu_items();
$siteFavicon = branding_asset('site_favicon','assets/img/seal.svg');
$siteHeaderLogo = branding_asset('site_header_logo','assets/img/akhikhan-logo-transparent.webp');

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
<link rel="stylesheet" href="<?=e(base_url('assets/css/style.css?v=20260929-reference-header1'))?>">
</head>
<body>
<a id="top"></a>
<div class="site-shell">

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
