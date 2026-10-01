<?php
$navCategories = categories();
$bySlug = [];
foreach ($navCategories as $cat) $bySlug[$cat['slug']] = $cat;
$mainMenuItems = main_menu_items();
$siteFavicon = branding_asset('site_favicon','assets/img/seal.svg');
$siteHeaderLogo = branding_asset('site_header_logo','assets/img/akhikhan-logo-transparent.webp');
$topbarRegion = setting('topbar_region_label','Унцукульский район');
$siteSocialLinks = social_links(true);
$publicAdminUser = admin_user();

$seoSiteName = 'АХИХЪАН';
$seoDefaultDescription = 'Сетевое издание Унцукульского района Республики Дагестан: новости, общество, культура, спорт, люди и история.';
$seoTitleText = trim((string)($pageTitle ?? ''));
$seoFullTitle = $seoTitleText !== ''
    ? $seoTitleText . ' — ' . $seoSiteName
    : 'АХИХЪАН — сетевое издание Унцукульского района';
$seoDescription = trim((string)($pageDescription ?? ''));
if ($seoDescription === '') $seoDescription = $seoDefaultDescription;

$requestPath = (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
$seoCanonical = trim((string)($seoCanonical ?? ''));
if ($seoCanonical === '') {
    $seoCanonical = $requestPath === '/' ? base_url() : base_url(ltrim($requestPath, '/'));
}

$seoImage = trim((string)($seoImage ?? ''));
if ($seoImage === '') {
    $seoImage = base_url('assets/img/akhikhan-logo-hq.webp');
} elseif (!preg_match('~^https?://~i', $seoImage)) {
    $seoImage = base_url(ltrim($seoImage, '/'));
}

$seoType = trim((string)($seoType ?? 'website')) ?: 'website';
$seoRobots = trim((string)($seoRobots ?? 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'));
$seoAuthor = trim((string)($seoAuthor ?? ''));
$seoPublishedTime = trim((string)($seoPublishedTime ?? ''));
$seoModifiedTime = trim((string)($seoModifiedTime ?? ''));
$seoJsonLd = isset($seoJsonLd) && is_array($seoJsonLd) ? $seoJsonLd : [];

$seoSameAs = [];
foreach ($siteSocialLinks as $social) {
    $url = trim((string)($social['url'] ?? ''));
    if (preg_match('~^https?://~i', $url)) $seoSameAs[] = $url;
}

$seoOrganization = [
    '@context' => 'https://schema.org',
    '@type' => 'NewsMediaOrganization',
    '@id' => base_url('#organization'),
    'name' => $seoSiteName,
    'alternateName' => 'Сетевое издание Унцукульского района',
    'url' => base_url(),
    'logo' => [
        '@type' => 'ImageObject',
        'url' => base_url($siteHeaderLogo),
    ],
];
if ($seoSameAs) $seoOrganization['sameAs'] = array_values(array_unique($seoSameAs));

$seoWebsite = [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    '@id' => base_url('#website'),
    'url' => base_url(),
    'name' => $seoSiteName,
    'description' => $seoDefaultDescription,
    'inLanguage' => 'ru-RU',
    'publisher' => ['@id' => base_url('#organization')],
];

array_unshift($seoJsonLd, $seoWebsite);
array_unshift($seoJsonLd, $seoOrganization);

function nav_link_for_slug(string $slug, string $fallbackLabel): string {
    global $bySlug;
    if (isset($bySlug[$slug])) return category_url($bySlug[$slug]);
    return base_url('search.php?q=' . rawurlencode($fallbackLabel));
}
?><!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#231d18">
<link rel="icon" href="<?=e(base_url($siteFavicon))?>">
<title><?=e($seoFullTitle)?></title>
<meta name="description" content="<?=e($seoDescription)?>">
<meta name="robots" content="<?=e($seoRobots)?>">
<link rel="canonical" href="<?=e($seoCanonical)?>">
<meta property="og:locale" content="ru_RU">
<meta property="og:site_name" content="<?=e($seoSiteName)?>">
<meta property="og:type" content="<?=e($seoType)?>">
<meta property="og:title" content="<?=e($seoFullTitle)?>">
<meta property="og:description" content="<?=e($seoDescription)?>">
<meta property="og:url" content="<?=e($seoCanonical)?>">
<meta property="og:image" content="<?=e($seoImage)?>">
<meta property="og:image:alt" content="<?=e($seoTitleText !== '' ? $seoTitleText : $seoSiteName)?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?=e($seoFullTitle)?>">
<meta name="twitter:description" content="<?=e($seoDescription)?>">
<meta name="twitter:image" content="<?=e($seoImage)?>">
<?php if($seoAuthor!==''):?><meta name="author" content="<?=e($seoAuthor)?>"><?php endif;?>
<?php if($seoPublishedTime!==''):?><meta property="article:published_time" content="<?=e($seoPublishedTime)?>"><?php endif;?>
<?php if($seoModifiedTime!==''):?><meta property="article:modified_time" content="<?=e($seoModifiedTime)?>"><?php endif;?>
<?php foreach($seoJsonLd as $jsonLd):?>
<script type="application/ld+json"><?=json_encode($jsonLd,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?></script>
<?php endforeach;?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Manrope:wght@400;500;600;700;800&family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Noto+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Noto+Serif:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=PT+Serif:ital,wght@0,400;0,700;1,400;1,700&family=Rubik:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<link rel="stylesheet" href="<?=e(base_url('assets/css/style.css?v=20261001-admin-account1'))?>">
</head>
<body>
<a id="top"></a>
<div class="site-shell">

<div class="site-topbar">
  <div class="wrap site-topbar-inner">
    <div class="site-topbar-left">
      <span class="topbar-region">
        <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
        <span><?=e($topbarRegion)?></span>
      </span>

      <span class="topbar-separator" aria-hidden="true"></span>

      <span class="topbar-weather" data-site-weather title="Погода в Унцукуле">
        <i class="fa-solid fa-cloud-sun topbar-weather-icon" aria-hidden="true"></i>
        <span data-site-weather-text>Погода · …°</span>
      </span>
    </div>

    <div class="site-topbar-right">
      <?php if($siteSocialLinks):?>
        <div class="topbar-socials" aria-label="Социальные сети">
          <?php foreach($siteSocialLinks as $social):
            $socialLabel=$social['label'] ?: social_service_name($social['service']);
          ?>
            <a href="<?=e($social['url'])?>" <?=str_starts_with(strtolower($social['url']),'mailto:')?'':'target="_blank" rel="noopener"'?> aria-label="<?=e($socialLabel)?>" title="<?=e($socialLabel)?>">
              <i class="<?=e(social_service_icon($social['service']))?>" aria-hidden="true"></i>
            </a>
          <?php endforeach;?>
        </div>
        <span class="topbar-separator" aria-hidden="true"></span>
      <?php endif;?>

      <?php if($publicAdminUser):?>
        <?php
          $publicAdminName=trim((string)($publicAdminUser['name']??'Администратор'));
          $publicAdminInitial=function_exists('mb_substr')
            ? mb_strtoupper(mb_substr($publicAdminName,0,1,'UTF-8'),'UTF-8')
            : strtoupper(substr($publicAdminName,0,1));
        ?>
        <div class="topbar-admin-account" data-topbar-admin-account>
          <button class="topbar-admin-trigger" type="button" data-topbar-admin-toggle aria-expanded="false" aria-haspopup="true">
            <span class="topbar-admin-avatar"><?=e($publicAdminInitial)?></span>
            <span class="topbar-admin-label">Админ</span>
            <i class="fa-solid fa-chevron-down topbar-admin-chevron" aria-hidden="true"></i>
          </button>
          <div class="topbar-admin-menu" data-topbar-admin-menu hidden>
            <div class="topbar-admin-profile">
              <span class="topbar-admin-profile-avatar"><?=e($publicAdminInitial)?></span>
              <span class="topbar-admin-profile-copy">
                <strong><?=e($publicAdminName)?></strong>
                <small><?=e(role_label((string)($publicAdminUser['role']??'editor')))?></small>
              </span>
            </div>
            <div class="topbar-admin-menu-links">
              <a href="<?=e(base_url('admin/'))?>" target="_blank" rel="noopener">
                <i class="fa-solid fa-gauge-high" aria-hidden="true"></i><span>Админ-панель</span>
              </a>
              <a href="<?=e(base_url('admin/profile.php'))?>" target="_blank" rel="noopener">
                <i class="fa-regular fa-user" aria-hidden="true"></i><span>Профиль</span>
              </a>
              <a class="is-logout" href="<?=e(base_url('admin/logout.php'))?>">
                <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i><span>Выйти</span>
              </a>
            </div>
          </div>
        </div>
      <?php else:?>
        <a class="topbar-admin-login" href="<?=e(base_url('admin/login.php'))?>" target="_blank" rel="noopener">
          <i class="fa-regular fa-user" aria-hidden="true"></i>
          <span>Вход в редакцию</span>
        </a>
      <?php endif;?>
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
