<?php
require __DIR__ . '/app/bootstrap.php';
if (!APP_INSTALLED) { header('Location: install.php'); exit; }
$pageTitle='О редакции';
$pageDescription='О сетевом издании «АХИХЪАН» Унцукульского района Республики Дагестан: тематика издания, редакционные принципы и связь с регионом.';
$seoCanonical=base_url('about.php');
require __DIR__.'/partials/header.php';
?>
<div class="wrap page-shell">
  <div class="page-head">
    <div><span class="heading-kicker">Сетевое издание</span><h1>О редакции</h1><p>«АХИХЪАН» — информационная площадка об Унцукульском районе, его людях, событиях, культуре и истории.</p></div>
    <div class="page-head-mark"></div>
  </div>
  <div class="info-grid">
    <section class="info-card">
      <h2>О чём мы пишем</h2>
      <p>В центре внимания — жизнь района: общественные события, развитие населённых пунктов, образование, культура, спорт, традиции, ремёсла, история и люди, чьи судьбы связаны с родным краем.</p>
      <p>Визуальный стиль издания строится на образах горного Дагестана и унцукульского декоративного искусства, чтобы современная цифровая газета сохраняла узнаваемую связь с местом.</p>
    </section>
    <aside class="info-card">
      <h2>Наши принципы</h2>
      <div class="info-list">
        <div><strong>Район прежде всего</strong><span>Темы, которые напрямую касаются жителей.</span></div>
        <div><strong>Уважение к наследию</strong><span>Культура, память, ремёсла и история.</span></div>
        <div><strong>Современная подача</strong><span>Удобный сайт для телефона и компьютера.</span></div>
      </div>
    </aside>
  </div>
</div>
<?php require __DIR__.'/partials/footer.php'; ?>
