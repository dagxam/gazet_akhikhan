<?php
require __DIR__ . '/app/bootstrap.php';
if (!APP_INSTALLED) { header('Location: install.php'); exit; }

$pageTitle='Политика использования cookie';
$pageDescription='Информация об использовании технических cookie на сайте «АХИХЪАН».';
$seoCanonical=base_url('cookies.php');
require __DIR__.'/partials/header.php';
?>
<div class="wrap page-shell legal-page-shell">
  <div class="page-head">
    <div>
      <span class="heading-kicker">Конфиденциальность</span>
      <h1>Политика использования cookie</h1>
      <p>Какие технические идентификаторы используются на сайте akhikhan.ru.</p>
    </div>
    <div class="page-head-mark" aria-hidden="true"></div>
  </div>
  <article class="legal-document">
    <section>
      <h2>1. Обычные посетители</h2>
      <p>Для обычного просмотра опубликованных материалов сайт не создаёт PHP-сессию и не устанавливает собственные рекламные или аналитические cookie.</p>
    </section>
    <section>
      <h2>2. Редакционная система</h2>
      <p>При входе в административную часть используется строго необходимый сессионный cookie PHP. Он нужен для аутентификации, CSRF-защиты и безопасности редакционной системы. Cookie имеет параметры HttpOnly и SameSite=Lax, а при работе по HTTPS — Secure.</p>
    </section>
    <section>
      <h2>3. Срок действия</h2>
      <p>Сессионный идентификатор используется только в пределах необходимой сессии. Административная сессия дополнительно завершается после длительного бездействия либо по достижении установленного максимального времени.</p>
    </section>
    <section>
      <h2>4. Управление cookie</h2>
      <p>Браузер позволяет удалять или блокировать cookie. Блокировка строго необходимых cookie приведёт к невозможности войти в редакционную систему, но не должна препятствовать чтению публичных материалов.</p>
    </section>
    <section>
      <h2>5. Связанные документы</h2>
      <p><a href="<?=e(base_url('privacy.php'))?>">Политика обработки персональных данных</a></p>
    </section>
  </article>
</div>
<?php require __DIR__.'/partials/footer.php'; ?>
