<?php $navCategories = categories(); ?><!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e(($pageTitle ?? '') ? $pageTitle . ' — ' . setting('site_name','AKHIKHAN.RU') : setting('site_name','AKHIKHAN.RU'))?></title>
<meta name="description" content="<?=e($pageDescription ?? setting('site_subtitle','Местная газета для наших людей'))?>">
<link rel="stylesheet" href="<?=e(base_url('assets/css/style.css'))?>">
</head>
<body>
<div class="topbar">
  <div class="wrap topbar-inner">
    <span><?=e(current_date_ru())?></span>
    <nav><a href="#">О газете</a><a href="#contacts">Контакты</a></nav>
  </div>
</div>
<header class="brand-header">
  <div class="wrap brand-grid">
    <a class="brand" href="<?=e(base_url())?>">
      <span class="brand-seal">1935</span>
      <span><strong><?=e(setting('site_name','AKHIKHAN.RU'))?></strong><small><?=e(setting('site_subtitle','Местная газета для наших людей'))?></small></span>
    </a>
    <div class="brand-motto">Наш край<br>Наши люди<br>Наша история</div>
    <div class="mountain-mark" aria-hidden="true">⌃⌃⌃</div>
  </div>
</header>
<nav class="main-nav">
  <div class="ornament"></div>
  <div class="wrap nav-inner">
    <a href="<?=e(base_url())?>" class="nav-home">Главная</a>
    <?php foreach($navCategories as $cat): ?>
      <a href="<?=e(category_url($cat))?>"><?=e($cat['name'])?></a>
    <?php endforeach; ?>
    <form class="search" action="<?=e(base_url('search.php'))?>" method="get"><input name="q" placeholder="Поиск"><button aria-label="Поиск">⌕</button></form>
  </div>
</nav>
<main>
