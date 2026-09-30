<?php
require __DIR__ . '/app/bootstrap.php';
if (!APP_INSTALLED) { header('Location: install.php'); exit; }
$pageTitle='Контакты редакции';
$pageDescription='Контакты редакции сетевого издания «АХИХЪАН» Унцукульского района Республики Дагестан.';
$seoCanonical=base_url('contacts.php');
$operatorName=trim(setting('pd_operator_name',''));
$operatorAddress=trim(setting('pd_operator_address',''));
$operatorEmail=trim(setting('pd_operator_email',''));
$operatorPhone=trim(setting('pd_operator_phone',''));
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
      <?php if($operatorName!==''):?><p><strong>Оператор персональных данных:</strong> <?=e($operatorName)?></p><?php endif;?>
      <?php if($operatorAddress!==''):?><p><strong>Адрес оператора:</strong> <?=e($operatorAddress)?></p><?php endif;?>
      <?php if($operatorPhone!==''):?><p><strong>Телефон оператора:</strong> <?=e($operatorPhone)?></p><?php endif;?>
      <?php if($operatorName==='' && $operatorAddress==='' && $operatorPhone===''):?>
        <p>Реквизиты редакции уточняются. До их подтверждения сайт не публикует вымышленные контактные данные.</p>
      <?php endif;?>
      <h2>Персональные данные</h2>
      <?php if($operatorEmail!==''):?>
        <p>Для запросов об обработке, уточнении и удалении персональных данных, а также для отзыва согласия: <a href="mailto:<?=e($operatorEmail)?>"><?=e($operatorEmail)?></a>.</p>
      <?php else:?>
        <p>Специальный e-mail для обращений будет указан после заполнения подтверждённых реквизитов редакцией.</p>
      <?php endif;?>
      <p><a href="<?=e(base_url('privacy.php'))?>">Политика обработки персональных данных →</a></p>
    </section>
    <aside class="info-card">
      <h2>Темы обращений</h2>
      <div class="info-list">
        <div><strong>Предложить новость</strong><span>События и инициативы района.</span></div>
        <div><strong>Обратиться в редакцию</strong><span>Вопросы по публикациям и материалам.</span></div>
        <div><strong>Реклама и сотрудничество</strong><span>Коммерческие и партнёрские запросы.</span></div>
        <div><strong>Персональные данные</strong><span>Доступ, исправление, удаление и отзыв согласия.</span></div>
      </div>
    </aside>
  </div>
</div>
<?php require __DIR__.'/partials/footer.php'; ?>
