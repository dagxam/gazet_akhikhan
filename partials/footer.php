</main>

<section class="heritage-band">
  <div class="wrap heritage-inner">
    <span class="heritage-side heritage-side-left" aria-hidden="true"></span>
    <div class="heritage-copy">
      <small>АХИХЪАН · УНЦУКУЛЬСКИЙ РАЙОН</small>
      <strong>« <?=e(setting('footer_quote','Сила народа — в его корнях, а будущее — в его людях'))?> »</strong>
    </div>
    <span class="heritage-side heritage-side-right" aria-hidden="true"></span>
  </div>
</section>

<footer class="site-footer" id="contacts">
  <div class="footer-ornament" aria-hidden="true"></div>

  <div class="wrap footer-main">
    <div class="footer-identity">
      <a href="<?=e(base_url())?>" class="footer-logo">
        <img src="<?=e(base_url('assets/img/akhikhan-logo-hq.webp?v=20260928-logo3'))?>" alt="АХИХЪАН" width="900" height="468">
      </a>
      <p>Новости и истории Унцукульского района Республики Дагестан. Рассказываем о событиях, людях, культуре и памяти родного края.</p>
      <div class="footer-region">Унцукульский район · Дагестан</div>
    </div>

    <div class="footer-column">
      <h3>Издание</h3>
      <a href="<?=e(base_url('about.php'))?>">О редакции</a>
      <a href="<?=e(base_url('news.php'))?>">Все новости</a>
      <a href="<?=e(base_url('advertising.php'))?>">Реклама</a>
      <a href="<?=e(base_url('contacts.php'))?>">Контакты</a>
    </div>

    <div class="footer-column">
      <h3>Рубрики</h3>
      <a href="<?=e(nav_link_for_slug('obschestvo','Общество'))?>">Общество</a>
      <a href="<?=e(nav_link_for_slug('kultura','Культура'))?>">Культура</a>
      <a href="<?=e(nav_link_for_slug('sport','Спорт'))?>">Спорт</a>
      <a href="<?=e(nav_link_for_slug('istoriya','История'))?>">История</a>
    </div>

    <div class="footer-contact">
      <span class="footer-contact-label">Мы в социальных сетях</span>
      <div class="footer-socials socials">
        <a href="#" aria-label="Telegram">TG</a>
        <a href="#" aria-label="VK">VK</a>
        <a href="#" aria-label="YouTube">YT</a>
        <a href="#" aria-label="Instagram">IG</a>
      </div>
      <a class="footer-contact-link" href="<?=e(base_url('contacts.php'))?>">Связаться с редакцией →</a>
    </div>
  </div>

  <div class="footer-bottom">
    <div class="wrap footer-bottom-inner">
      <span>© <?=date('Y')?> АХИХЪАН. Все права защищены.</span>
      <span>Сетевое издание Унцукульского района</span>
      <a href="#top" class="back-top">Наверх ↑</a>
    </div>
  </div>
</footer>

<script src="<?=e(base_url('assets/js/app.js?v=20260928-hover1'))?>"></script>
</div>
</body>
</html>
