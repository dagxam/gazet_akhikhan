<?php
require __DIR__ . '/app/bootstrap.php';
if (!APP_INSTALLED) { header('Location: install.php'); exit; }
$pageTitle='Контакты редакции';
$pageDescription='Контакты редакции сетевого издания «АХИХЪАН» Унцукульского района Республики Дагестан.';
$seoCanonical=base_url('contacts.php');
require __DIR__.'/partials/header.php';
?>
<div class="wrap page-shell">
  <div class="page-head">
    <div><span class="heading-kicker">Редакция «АХИХЪАН»</span><h1>Контакты</h1><p>Унцукульский район · Республика Дагестан</p></div>
    <div class="page-head-mark"></div>
  </div>
  <div class="info-grid">
    <section class="info-card">
      <h2>Связь с редакцией</h2>
      <p>Здесь будут размещены официальные контактные данные редакции: электронная почта, телефон, адрес и ссылки на социальные сети.</p>
      <p>До заполнения реквизитов через административную панель сайт не публикует вымышленные контакты.</p>
    </section>
    <aside class="info-card">
      <h2>Темы обращений</h2>
      <div class="info-list">
        <div><strong>Предложить новость</strong><span>События и инициативы района.</span></div>
        <div><strong>Обратиться в редакцию</strong><span>Вопросы по публикациям и материалам.</span></div>
        <div><strong>Реклама и сотрудничество</strong><span>Коммерческие и партнёрские запросы.</span></div>
      </div>
    </aside>
  </div>
</div>
<?php require __DIR__.'/partials/footer.php'; ?>
