</main>
<?php
$siteFooterLogo = branding_asset('site_footer_logo','assets/img/akhikhan-logo-hq.webp');
$footerSocialLinks = social_links(true);
?>

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
      <a href="#top" class="back-top">Наверх ↑</a>
    </div>
  </div>
</footer>

<script src="<?=e(base_url('assets/js/app.js?v=20260929-topbar1'))?>"></script>
</div>
</body>
</html>
