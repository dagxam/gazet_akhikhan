<?php
$navCategories = categories();
$bySlug = [];
foreach ($navCategories as $cat) $bySlug[$cat['slug']] = $cat;
$mainMenuItems = main_menu_items();

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
<title><?=e(($pageTitle ?? '') ? $pageTitle . ' — АХИХЪАН' : 'АХИХЪАН — сетевое издание Унцукульского района')?></title>
<meta name="description" content="<?=e($pageDescription ?? 'Сетевое издание Унцукульского района Республики Дагестан: новости, общество, культура, спорт, люди и история.')?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?=e(base_url('assets/css/style.css?v=20260928-menuoverflow2'))?>">
</head>
<body>
<a id="top"></a>
<div class="site-shell">

<div class="utility-bar">
  <div class="wrap utility-inner">
    <div class="utility-date"><?=e(current_date_ru())?></div>
    <div class="utility-place"><span class="utility-dot"></span> Унцукульский район · Республика Дагестан</div>
    <div class="utility-links">
      <a href="<?=e(base_url('about.php'))?>">О редакции</a>
      <a href="<?=e(base_url('advertising.php'))?>">Реклама</a>
      <a href="<?=e(base_url('contacts.php'))?>">Контакты</a>
      <a class="utility-login" href="<?=e(base_url('admin/login.php'))?>">Вход</a>
    </div>
  </div>
</div>

<header class="masthead">
  <div class="masthead-ornament masthead-ornament-left" aria-hidden="true"></div>
  <div class="masthead-ornament masthead-ornament-right" aria-hidden="true"></div>

  <div class="wrap masthead-inner">
    <div class="masthead-brand">
      <a class="masthead-logo" href="<?=e(base_url())?>" aria-label="АХИХЪАН — главная">
        <img src="<?=e(base_url('assets/img/akhikhan-logo-transparent.webp?v=20260928-transparent-logo1'))?>" alt="АХИХЪАН — сетевое издание Унцукульского района" width="900" height="386">
      </a>
    </div>

    <div class="masthead-note">
      <span class="masthead-note-kicker">Республика Дагестан</span>
      <strong>Говорим о важном.<br>Сохраняем связь поколений.</strong>
      <small>События района, общественная жизнь, традиции и люди родного края.</small>
      <span class="masthead-note-mark" aria-hidden="true">У</span>
    </div>
  </div>

  <div class="masthead-pattern-line" aria-hidden="true"></div>
</header>

<nav class="primary-nav" aria-label="Основная навигация">
  <div class="nav-pattern nav-pattern-left" aria-hidden="true"></div>
  <div class="wrap primary-nav-inner">
    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-menu">
      <span></span><span></span><span></span><b>Меню</b>
    </button>

    <div class="desktop-menu-shell" data-menu-shell>
      <div class="site-menu" id="site-menu">
        <?php foreach($mainMenuItems as $menuItem):?>
          <a href="<?=e(main_menu_url($menuItem['url']))?>" <?=$menuItem['open_new_tab']?'target="_blank" rel="noopener"':''?>><?=e($menuItem['label'])?></a>
        <?php endforeach;?>
      </div>

      <button class="menu-overflow-toggle" type="button" data-menu-overflow-toggle aria-expanded="false" aria-controls="menu-overflow-panel" hidden>
        <span>Ещё</span>
        <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M5 7.5l5 5 5-5"></path></svg>
      </button>

      <div class="menu-overflow-panel" id="menu-overflow-panel" data-menu-overflow-panel></div>
    </div>

    <form class="header-search" action="<?=e(base_url('search.php'))?>" method="get">
      <input name="q" aria-label="Поиск по сайту" placeholder="Поиск">
      <button type="submit" aria-label="Найти">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"></circle><path d="M16 16l4 4"></path></svg>
      </button>
    </form>
  </div>
  <div class="nav-pattern nav-pattern-right" aria-hidden="true"></div>
</nav>

<div class="edition-line">
  <div class="wrap edition-line-inner">
    <span class="edition-label">Сегодня в районе</span>
    <span>Новости Унцукульского района, материалы о людях, культуре и истории родного края</span>
    <a href="<?=e(base_url('news.php'))?>">Все материалы <b>→</b></a>
  </div>
</div>

<main>
