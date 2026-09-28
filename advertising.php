<?php
require __DIR__ . '/app/bootstrap.php';
if (!APP_INSTALLED) { header('Location: install.php'); exit; }
$pageTitle='Реклама';
$pageDescription='Информация о размещении рекламы в сетевом издании «АХИХЪАН».';
require __DIR__.'/partials/header.php';
?>
<div class="wrap page-shell">
  <div class="page-head">
    <div><span class="heading-kicker">Для организаций и предпринимателей</span><h1>Реклама</h1><p>Информационные и рекламные размещения в сетевом издании «АХИХЪАН».</p></div>
    <div class="page-head-mark"></div>
  </div>
  <div class="info-grid">
    <section class="info-card">
      <h2>Форматы размещения</h2>
      <p>На сайте можно использовать нативные публикации, информационные материалы, баннерные позиции и специальные проекты. Конкретный формат и оформление согласуются с редакцией.</p>
      <div class="info-list">
        <div><strong>Публикация</strong><span>Отдельный материал в ленте издания.</span></div>
        <div><strong>Баннер</strong><span>Визуальное размещение на страницах сайта.</span></div>
        <div><strong>Спецпроект</strong><span>Расширенный материал с индивидуальной подачей.</span></div>
      </div>
    </section>
    <aside class="info-card">
      <h2>Связаться с редакцией</h2>
      <p>Для обсуждения условий размещения используйте страницу контактов. Контактные данные можно будет добавить в настройках сайта после утверждения редакцией.</p>
      <p><a class="story-button" href="<?=e(base_url('contacts.php'))?>">Перейти к контактам <span>→</span></a></p>
    </aside>
  </div>
</div>
<?php require __DIR__.'/partials/footer.php'; ?>
