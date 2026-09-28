<?php
$navCategories = categories();
$bySlug = [];
foreach ($navCategories as $cat) $bySlug[$cat['slug']] = $cat;

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
<title><?=e(($pageTitle ?? '') ? $pageTitle . ' — ' . setting('site_name','AKHIKHAN.RU') : setting('site_name','AKHIKHAN.RU'))?></title>
<meta name="description" content="<?=e($pageDescription ?? setting('site_subtitle','Местная газета для наших людей'))?>">
<link rel="stylesheet" href="<?=e(base_url('assets/css/style.css?v=20260928-ornaments'))?>">
</head>
<body>
<div class="site-paper">
<div class="topbar">
  <div class="wrap topbar-inner">
    <span><?=e(current_date_ru())?></span>
    <div class="topbar-right">
      <nav><a href="#about">О газете</a><i></i><a href="#advertising">Реклама</a><i></i><a href="#contacts">Контакты</a></nav>
      <div class="socials" aria-label="Социальные сети">
        <a href="#" aria-label="Telegram">➤</a>
        <a href="#" aria-label="VK">vk</a>
        <a href="#" aria-label="YouTube">▶</a>
        <a href="#" aria-label="Instagram">◎</a>
      </div>
    </div>
  </div>
</div>

<header class="brand-header">
  <div class="wrap brand-grid">
    <a class="brand brand-logo-link" href="<?=e(base_url())?>">
      <img class="brand-logo-image" src="<?=e(base_url('assets/img/akhikhan-logo-user.jpg'))?>" alt="АХИХЪАН — сетевое издание Унцукульского района">
    </a>
    <div class="brand-motto">Наш край<br>Наши люди<br>Наша история</div>
    <div class="district-mark">
      <img src="<?=e(base_url('assets/img/mountains.svg'))?>" alt="">
      <span>Унцукульский район<br>Дагестан</span>
    </div>
  </div>
</header>

<nav class="main-nav" aria-label="Основное меню">
  <div class="nav-ornament nav-ornament-left" aria-hidden="true"></div>
  <div class="wrap nav-inner">
    <a href="<?=e(base_url())?>">Главная</a>
    <a href="<?=e(base_url('search.php'))?>">Новости</a>
    <a href="<?=e(nav_link_for_slug('obschestvo','Общество'))?>">Общество</a>
    <a href="<?=e(nav_link_for_slug('ekonomika','Экономика'))?>">Экономика</a>
    <a href="<?=e(nav_link_for_slug('kultura','Культура'))?>">Культура</a>
    <a href="<?=e(nav_link_for_slug('sport','Спорт'))?>">Спорт</a>
    <a href="<?=e(nav_link_for_slug('lyudi','Люди'))?>">Люди</a>
    <a href="<?=e(nav_link_for_slug('istoriya','История'))?>">История</a>
    <a href="<?=e(base_url('search.php?q=' . rawurlencode('Фото')))?>">Фото</a>
    <a href="<?=e(base_url('search.php?q=' . rawurlencode('Видео')))?>">Видео</a>
    <form class="search" action="<?=e(base_url('search.php'))?>" method="get">
      <input name="q" aria-label="Поиск по сайту" placeholder="Поиск">
      <button type="button" class="search-toggle" aria-label="Открыть поиск">⌕</button>
    </form>
  </div>
  <div class="nav-ornament nav-ornament-right" aria-hidden="true"></div>
</nav>
<main>
