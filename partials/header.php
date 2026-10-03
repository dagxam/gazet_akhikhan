<?php
$navCategories = categories();
$bySlug = [];
foreach ($navCategories as $cat) $bySlug[$cat['slug']] = $cat;
$mainMenuItems = main_menu_items();
$siteFavicon = branding_asset('site_favicon','assets/img/seal.svg');
$siteFaviconPath = (string)(parse_url($siteFavicon, PHP_URL_PATH) ?: $siteFavicon);
$siteFaviconExt = strtolower(pathinfo($siteFaviconPath, PATHINFO_EXTENSION));
$searchFavicon = in_array($siteFaviconExt,['bmp','gif','ico','png','jpg','jpeg','ppm','tif','tiff'],true)
    ? $siteFavicon
    : 'assets/img/favicon-search.png';
$siteHeaderLogo = branding_asset('site_header_logo','assets/img/akhikhan-logo-transparent.webp');
$topbarRegion = setting('topbar_region_label','Унцукульский район');
$siteSocialLinks = social_links(true);
$publicAdminUser = admin_user();

$ageRatingEnabled=setting('age_rating_enabled','1')==='1';
$ageRatingLabel=trim(setting('age_rating_label','16+')) ?: '16+';
$ageRatingText=match($ageRatingLabel){
    '0+'=>'для детей любого возраста',
    '6+'=>'для детей старше 6 лет',
    '12+'=>'для детей старше 12 лет',
    '18+'=>'запрещено для детей',
    default=>'для детей старше 16 лет',
};
$accessibilityEnabled=setting('accessibility_enabled','1')==='1';
$accessibilityLabel=trim(setting('accessibility_label','Версия для слабовидящих')) ?: 'Версия для слабовидящих';
$accessibilityPanelTitle=trim(setting('accessibility_panel_title','Версия для слабовидящих')) ?: 'Версия для слабовидящих';
$accessibilityPanelText=trim(setting('accessibility_panel_text','Настройте отображение сайта под себя: размер текста, контраст, интервалы, изображения и анимацию.')) ?: 'Настройте отображение сайта под себя.';
$accessibilityDefaultFont=in_array(setting('accessibility_default_font','100'),['100','125','150','200'],true) ? setting('accessibility_default_font','100') : '100';
$accessibilityDefaultContrast=in_array(setting('accessibility_default_contrast','normal'),['normal','black-white','white-black','yellow-black'],true) ? setting('accessibility_default_contrast','normal') : 'normal';
$accessibilityDefaultSpacing=in_array(setting('accessibility_default_spacing','normal'),['normal','wide'],true) ? setting('accessibility_default_spacing','normal') : 'normal';
$accessibilityDefaultGrayscale=setting('accessibility_default_grayscale','0')==='1' ? 'true' : 'false';
$accessibilityDefaultMotion=setting('accessibility_default_reduce_motion','1')==='1' ? 'reduce' : 'normal';
$accessibilityShowFont=setting('accessibility_control_font','1')==='1';
$accessibilityShowContrast=setting('accessibility_control_contrast','1')==='1';
$accessibilityShowSpacing=setting('accessibility_control_spacing','1')==='1';
$accessibilityShowGrayscale=setting('accessibility_control_grayscale','1')==='1';
$accessibilityShowMotion=setting('accessibility_control_motion','1')==='1';
$accessibilityPanelBg=setting('accessibility_panel_bg','#fffdf9');
$accessibilityPanelTextColor=setting('accessibility_panel_text_color','#302923');
$accessibilityPanelAccent=setting('accessibility_panel_accent','#765132');
$accessibilityPanelBorder=setting('accessibility_panel_border','#d8c7b4');
$accessibilityPrimaryBg=setting('accessibility_primary_bg','#5d402a');
$accessibilityPrimaryText=setting('accessibility_primary_text','#ffffff');

$accessibilityPanelBgCss=css_safe_color($accessibilityPanelBg,'#fffdf9');
$accessibilityPanelTextCss=css_safe_color($accessibilityPanelTextColor,'#302923');
$accessibilityPanelAccentCss=css_safe_color($accessibilityPanelAccent,'#765132');
$accessibilityPanelBorderCss=css_safe_color($accessibilityPanelBorder,'#d8c7b4');
$accessibilityPrimaryBgCss=css_safe_color($accessibilityPrimaryBg,'#5d402a');
$accessibilityPrimaryTextCss=css_safe_color($accessibilityPrimaryText,'#ffffff');

$seoSiteName = trim(setting('site_name','АХИХЪАН')) ?: 'АХИХЪАН';
$seoSiteAlternateName = 'Сетевое издание Унцукульского района';
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
$seoArticleSection = trim((string)($seoArticleSection ?? ''));
$seoJsonLd = isset($seoJsonLd) && is_array($seoJsonLd) ? $seoJsonLd : [];

$seoImageWidth = 0;
$seoImageHeight = 0;
$seoImageMime = '';
$seoImagePath = '';
$seoImageUrlPath = (string)(parse_url((string)$seoImage, PHP_URL_PATH) ?: '');
if ($seoImageUrlPath !== '') {
    $seoImagePath = ROOT_PATH . '/' . ltrim($seoImageUrlPath, '/');
}
if ($seoImagePath !== '' && is_file($seoImagePath)) {
    $imageInfo = @getimagesize($seoImagePath);
    if (is_array($imageInfo)) {
        $seoImageWidth = (int)($imageInfo[0] ?? 0);
        $seoImageHeight = (int)($imageInfo[1] ?? 0);
        $seoImageMime = trim((string)($imageInfo['mime'] ?? ''));
    }
}

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
    'alternateName' => $seoSiteAlternateName,
    'url' => base_url(),
    'logo' => [
        '@type' => 'ImageObject',
        '@id' => base_url('#logo'),
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
    'alternateName' => $seoSiteAlternateName,
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
<meta name="application-name" content="<?=e($seoSiteName)?>">
<link rel="icon" href="<?=e(base_url($searchFavicon))?>">
<link rel="shortcut icon" href="<?=e(base_url($searchFavicon))?>">
<link rel="apple-touch-icon" href="<?=e(base_url($searchFavicon))?>">
<link rel="sitemap" type="application/xml" title="Sitemap" href="<?=e(base_url('sitemap.xml'))?>">
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
<meta property="og:image:secure_url" content="<?=e($seoImage)?>">
<meta property="og:image:alt" content="<?=e($seoTitleText !== '' ? $seoTitleText : $seoSiteName)?>">
<?php if($seoImageWidth>0):?><meta property="og:image:width" content="<?=e((string)$seoImageWidth)?>"><?php endif;?>
<?php if($seoImageHeight>0):?><meta property="og:image:height" content="<?=e((string)$seoImageHeight)?>"><?php endif;?>
<?php if($seoImageMime!==''):?><meta property="og:image:type" content="<?=e($seoImageMime)?>"><?php endif;?>
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?=e($seoFullTitle)?>">
<meta name="twitter:description" content="<?=e($seoDescription)?>">
<meta name="twitter:image" content="<?=e($seoImage)?>">
<meta name="twitter:image:alt" content="<?=e($seoTitleText !== '' ? $seoTitleText : $seoSiteName)?>">
<?php if($seoAuthor!==''):?><meta name="author" content="<?=e($seoAuthor)?>"><?php endif;?>
<?php if($seoPublishedTime!==''):?><meta property="article:published_time" content="<?=e($seoPublishedTime)?>"><?php endif;?>
<?php if($seoModifiedTime!==''):?><meta property="article:modified_time" content="<?=e($seoModifiedTime)?>"><?php endif;?>
<?php if($seoArticleSection!==''):?><meta property="article:section" content="<?=e($seoArticleSection)?>"><?php endif;?>
<?php foreach($seoJsonLd as $jsonLd):?>
<script type="application/ld+json" nonce="<?=e(csp_nonce())?>"><?=json_encode($jsonLd,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?></script>
<?php endforeach;?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Manrope:wght@400;500;600;700;800&family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Noto+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Noto+Serif:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=PT+Serif:ital,wght@0,400;0,700;1,400;1,700&family=Rubik:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<link rel="stylesheet" href="<?=e(base_url('assets/css/style.css?v=20261003-security4'))?>">
<style nonce="<?=e(csp_nonce())?>">
.topbar-accessibility{--a11y-accent:<?=e($accessibilityPanelAccentCss)?>}
.accessibility-panel{--a11y-panel-bg:<?=e($accessibilityPanelBgCss)?>;--a11y-panel-text:<?=e($accessibilityPanelTextCss)?>;--a11y-panel-accent:<?=e($accessibilityPanelAccentCss)?>;--a11y-panel-border:<?=e($accessibilityPanelBorderCss)?>;--a11y-primary-bg:<?=e($accessibilityPrimaryBgCss)?>;--a11y-primary-text:<?=e($accessibilityPrimaryTextCss)?>}
</style>
</head>
<body>
<a class="skip-link" href="#main-content">Перейти к основному содержанию</a>
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
              <?php $socialAsset=social_service_asset((string)$social['service'],'black'); ?>
              <?php if($socialAsset!==''):?>
                <img class="social-service-image" src="<?=e(base_url($socialAsset))?>" alt="" aria-hidden="true">
              <?php else:?>
                <i class="<?=e(social_service_icon($social['service']))?>" aria-hidden="true"></i>
              <?php endif;?>
            </a>
          <?php endforeach;?>
        </div>
        <span class="topbar-separator" aria-hidden="true"></span>
      <?php endif;?>

      <?php if($ageRatingEnabled):?>
        <span class="topbar-age-rating" title="<?=e($ageRatingText)?>" aria-label="Возрастное ограничение <?=e($ageRatingLabel)?>, <?=e($ageRatingText)?>">
          <b><?=e($ageRatingLabel)?></b>
          <span><?=e($ageRatingText)?></span>
        </span>
      <?php endif;?>

      <?php if($accessibilityEnabled):?>
        <button class="topbar-accessibility" type="button" data-accessibility-toggle aria-expanded="false" aria-controls="accessibility-panel">
          <span class="topbar-accessibility-icon"><i class="fa-regular fa-eye" aria-hidden="true"></i></span>
          <span><?=e($accessibilityLabel)?></span>
          <b class="topbar-accessibility-state" data-accessibility-state-label>Выкл.</b>
        </button>
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
              <form class="topbar-logout-form" method="post" action="<?=e(base_url('admin/logout.php'))?>">
                <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                <button class="is-logout topbar-logout-button" type="submit">
                  <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i><span>Выйти</span>
                </button>
              </form>
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

<?php if($accessibilityEnabled):?>
<section class="accessibility-panel"
         id="accessibility-panel"
         data-accessibility-panel
         data-default-font="<?=e($accessibilityDefaultFont)?>"
         data-default-contrast="<?=e($accessibilityDefaultContrast)?>"
         data-default-spacing="<?=e($accessibilityDefaultSpacing)?>"
         data-default-grayscale="<?=e($accessibilityDefaultGrayscale)?>"
         data-default-motion="<?=e($accessibilityDefaultMotion)?>"
         hidden
         role="dialog"
         aria-modal="false"
         aria-labelledby="accessibility-panel-title">
  <div class="wrap accessibility-panel-inner">
    <div class="accessibility-panel-hero">
      <div class="accessibility-panel-symbol"><i class="fa-regular fa-eye" aria-hidden="true"></i></div>
      <div class="accessibility-panel-copy">
        <span class="accessibility-panel-kicker">Доступная версия</span>
        <h2 id="accessibility-panel-title"><?=e($accessibilityPanelTitle)?></h2>
        <p><?=e($accessibilityPanelText)?></p>
      </div>
      <button class="accessibility-close" type="button" data-accessibility-close aria-label="Закрыть настройки доступности">
        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
      </button>
    </div>

    <div class="accessibility-quick-presets" aria-label="Быстрые режимы">
      <button type="button" data-a11y-preset="comfortable">
        <i class="fa-solid fa-font"></i><span><b>Крупный текст</b><small>150% · больше интервал</small></span>
      </button>
      <button type="button" data-a11y-preset="contrast">
        <i class="fa-solid fa-circle-half-stroke"></i><span><b>Высокий контраст</b><small>белый текст на чёрном</small></span>
      </button>
      <button type="button" data-a11y-preset="calm">
        <i class="fa-solid fa-eye-low-vision"></i><span><b>Спокойный режим</b><small>125% · без анимации</small></span>
      </button>
    </div>

    <div class="accessibility-controls">
      <?php if($accessibilityShowFont):?>
      <fieldset class="accessibility-control-group accessibility-control-font">
        <legend><i class="fa-solid fa-font"></i> Размер текста</legend>
        <div class="accessibility-choice-row accessibility-font-row" data-a11y-font-group>
          <button type="button" data-a11y-font="100"><b>A</b><span>100%</span></button>
          <button type="button" data-a11y-font="125"><b>A+</b><span>125%</span></button>
          <button type="button" data-a11y-font="150"><b>A++</b><span>150%</span></button>
          <button type="button" data-a11y-font="200"><b>A+++</b><span>200%</span></button>
        </div>
      </fieldset>
      <?php endif;?>

      <?php if($accessibilityShowContrast):?>
      <fieldset class="accessibility-control-group accessibility-control-contrast">
        <legend><i class="fa-solid fa-circle-half-stroke"></i> Цвет и контраст</legend>
        <div class="accessibility-choice-row accessibility-contrast-row" data-a11y-contrast-group>
          <button type="button" data-a11y-contrast="normal"><span class="a11y-swatch is-normal">А</span><span>Обычный</span></button>
          <button type="button" data-a11y-contrast="black-white"><span class="a11y-swatch is-black-white">А</span><span>Чёрный / белый</span></button>
          <button type="button" data-a11y-contrast="white-black"><span class="a11y-swatch is-white-black">А</span><span>Белый / чёрный</span></button>
          <button type="button" data-a11y-contrast="yellow-black"><span class="a11y-swatch is-yellow-black">А</span><span>Жёлтый / чёрный</span></button>
        </div>
      </fieldset>
      <?php endif;?>

      <?php if($accessibilityShowSpacing||$accessibilityShowGrayscale||$accessibilityShowMotion):?>
      <fieldset class="accessibility-control-group accessibility-control-display">
        <legend><i class="fa-solid fa-sliders"></i> Дополнительно</legend>
        <div class="accessibility-toggle-row">
          <?php if($accessibilityShowSpacing):?>
            <button type="button" data-a11y-spacing aria-pressed="false"><i class="fa-solid fa-text-width"></i><span><b>Интервалы</b><small>увеличить расстояние между словами и строками</small></span></button>
          <?php endif;?>
          <?php if($accessibilityShowGrayscale):?>
            <button type="button" data-a11y-grayscale aria-pressed="false"><i class="fa-regular fa-image"></i><span><b>Ч/б изображения</b><small>убрать цвет с фотографий</small></span></button>
          <?php endif;?>
          <?php if($accessibilityShowMotion):?>
            <button type="button" data-a11y-motion aria-pressed="false"><i class="fa-solid fa-person-walking"></i><span><b>Без анимации</b><small>отключить декоративное движение</small></span></button>
          <?php endif;?>
        </div>
      </fieldset>
      <?php endif;?>
    </div>

    <div class="accessibility-panel-actions">
      <div class="accessibility-current-mode">
        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
        <span><b>Режим активен</b><small data-accessibility-summary>Настройки применены</small></span>
      </div>
      <button class="accessibility-reset" type="button" data-accessibility-reset><i class="fa-solid fa-arrow-rotate-left"></i> Сбросить настройки</button>
      <button class="accessibility-standard" type="button" data-accessibility-standard><i class="fa-regular fa-eye"></i> Обычная версия сайта</button>
      <span class="accessibility-status" data-accessibility-status aria-live="polite"></span>
    </div>
  </div>
</section>
<?php endif;?>

<header class="reference-header" data-reference-header>
  <div class="wrap reference-header-inner">
    <div class="reference-brand">
      <a class="reference-logo" href="<?=e(base_url())?>" aria-label="АХИХЪАН — главная">
        <img src="<?=e(base_url($siteHeaderLogo))?>" alt="АХИХЪАН — сетевое издание Унцукульского района">
      </a>
      <span class="reference-brand-caption">
        <b>Сетевое издание</b>
        <small>Унцукульский район</small>
      </span>
    </div>

    <button class="menu-toggle reference-mobile-toggle" type="button" aria-expanded="false" aria-controls="site-menu">
      <span></span><span></span><span></span><b>Меню</b>
    </button>

    <div class="desktop-menu-shell reference-menu-shell" data-menu-shell>
      <nav class="site-menu reference-site-menu" id="site-menu" aria-label="Основная навигация">
        <?php foreach($mainMenuItems as $menuItem):
          $menuHref=main_menu_url((string)$menuItem['url']);
          $menuPath=(string)(parse_url($menuHref,PHP_URL_PATH) ?: '/');
          $currentPath=rtrim($requestPath,'/') ?: '/';
          $normalizedMenuPath=rtrim($menuPath,'/') ?: '/';
          $isCurrentMenu=$currentPath===$normalizedMenuPath;
        ?>
          <a class="<?=$isCurrentMenu?'is-current':''?>" href="<?=e($menuHref)?>" <?=$menuItem['open_new_tab']?'target="_blank" rel="noopener"':''?> <?=$isCurrentMenu?'aria-current="page"':''?>><?=e($menuItem['label'])?></a>
        <?php endforeach;?>
      </nav>

      <button class="menu-overflow-toggle reference-overflow-toggle" type="button" data-menu-overflow-toggle aria-expanded="false" aria-controls="menu-overflow-panel" aria-label="Показать дополнительные пункты меню" hidden>
        <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M5.5 7.5l4.5 4.5 4.5-4.5"></path></svg>
      </button>

      <div class="menu-overflow-panel reference-overflow-panel" id="menu-overflow-panel" data-menu-overflow-panel></div>
    </div>
  </div>
</header>

<main id="main-content" tabindex="-1">
