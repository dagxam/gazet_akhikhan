</main>
<?php
$siteFooterLogo = branding_asset('site_footer_logo','assets/img/akhikhan-logo-hq.webp');
$footerSocialLinks = social_links(true);
?>

<section class="heritage-band" aria-label="Декоративная полоса издания «АХИХЪАН»">
  <div class="wrap heritage-inner">
    <div class="heritage-copy">
      <small>АХИХЪАН · УНЦУКУЛЬСКИЙ РАЙОН</small>
      <strong>« <?=e(setting('footer_quote','Сила народа — в его корнях, а будущее — в его людях'))?> »</strong>
    </div>
  </div>
</section>

<footer class="site-footer" id="contacts">
  <div class="footer-ornament" aria-hidden="true"></div>

  <div class="wrap footer-main">
    <div class="footer-identity">
      <a href="<?=e(base_url())?>" class="footer-logo">
        <img src="<?=e(base_url($siteFooterLogo))?>" alt="АХИХЪАН">
      </a>
      <p>Новости и истории Унцукульского района Республики Дагестан. Рассказываем о событиях, людях, культуре и памяти родного края.</p>
      <div class="footer-region">Унцукульский район · Дагестан</div>
    </div>

    <div class="footer-column">
      <h3>Издание</h3>
      <a href="<?=e(base_url('about.php'))?>">О редакции</a>
      <a href="<?=e(base_url('news.php'))?>">Все новости</a>
      <a href="<?=e(base_url('gallery.php'))?>">Фото</a>
      <a href="<?=e(base_url('videos.php'))?>">Видео</a>
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
        <?php foreach($footerSocialLinks as $social):
          $socialLabel=$social['label'] ?: social_service_name($social['service']);
        ?>
          <a href="<?=e($social['url'])?>" <?=str_starts_with(strtolower($social['url']),'mailto:')?'':'target="_blank" rel="noopener"'?> aria-label="<?=e($socialLabel)?>" title="<?=e($socialLabel)?>">
            <i class="<?=e(social_service_icon($social['service']))?>" aria-hidden="true"></i>
          </a>
        <?php endforeach;?>
      </div>
      <a class="footer-contact-link" href="<?=e(base_url('contacts.php'))?>">Связаться с редакцией →</a>
    </div>
  </div>

  <div class="footer-bottom">
    <div class="wrap footer-bottom-inner">
      <span>© <?=date('Y')?> АХИХЪАН. Все права защищены.</span>
      <span>Сетевое издание Унцукульского района</span>
      <nav class="footer-legal-links" aria-label="Правовая информация">
        <a href="<?=e(base_url('privacy.php'))?>">Персональные данные</a>
        <a href="<?=e(base_url('personal-data-consent.php'))?>">Согласие</a>
        <a href="<?=e(base_url('cookies.php'))?>">Cookie</a>
      </nav>
      <a href="#top" class="back-top">Наверх ↑</a>
    </div>
  </div>
</footer>

<div class="privacy-notice" data-privacy-notice hidden role="dialog" aria-live="polite" aria-label="Cookie и конфиденциальность">
  <div class="privacy-notice-card">
    <div class="privacy-notice-icon" aria-hidden="true">
      <svg viewBox="0 0 24 24"><path d="M12 3l7 3v5c0 4.7-2.7 8.2-7 10-4.3-1.8-7-5.3-7-10V6l7-3z"></path><path d="M9.3 12.1l1.8 1.8 3.8-4"></path></svg>
    </div>
    <div class="privacy-notice-copy">
      <strong>Cookie и конфиденциальность</strong>
      <p>Для работы редакционной системы используются необходимые технические cookie. Некоторые страницы также обращаются к внешним сервисам для шрифтов, иконок, погоды и предпросмотра PDF.</p>
      <div class="privacy-notice-links">
        <a href="<?=e(base_url('privacy.php'))?>">Персональные данные</a>
        <a href="<?=e(base_url('cookies.php'))?>">Подробнее о cookie</a>
      </div>
    </div>
    <div class="privacy-notice-actions">
      <button type="button" class="privacy-notice-accept" data-privacy-accept>Принять</button>
    </div>
  </div>
</div>

<script src="<?=e(base_url('assets/js/app.js?v=20261001-privacy-banner1'))?>"></script>
</div>
</body>
</html>
