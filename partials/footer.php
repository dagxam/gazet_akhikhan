</main>
<?php
$siteFooterLogo = branding_asset('site_footer_logo','assets/img/akhikhan-logo-hq.webp');
$footerSocialLinks = social_links(true);

$privacyBannerEnabled=setting('privacy_banner_enabled','1')==='1';
$privacyBannerTitle=setting('privacy_banner_title','Cookie и конфиденциальность');
$privacyBannerText=setting('privacy_banner_text','Для работы редакционной системы используются необходимые технические cookie. Некоторые страницы также обращаются к внешним сервисам для шрифтов, иконок, погоды и предпросмотра PDF.');
$privacyBannerAccept=trim(setting('privacy_banner_accept_label','Принять')) ?: 'Принять';
$privacyBannerVersion=trim(setting('privacy_banner_version','1')) ?: '1';

$privacyBannerColor=static function(string $key,string $fallback): string {
  $value=trim(setting($key,$fallback));
  return preg_match('/^#[0-9a-fA-F]{6}$/',$value) ? strtolower($value) : $fallback;
};
$privacyBannerUrl=static function(string $value,string $fallback): string {
  $value=trim($value);
  if($value==='') $value=$fallback;
  if(preg_match('~^(?:https?://|mailto:|tel:)~i',$value)) return $value;
  return base_url(ltrim($value,'/'));
};

$privacyBannerLink1Label=trim(setting('privacy_banner_link1_label','Персональные данные'));
$privacyBannerLink1Url=$privacyBannerUrl(setting('privacy_banner_link1_url','privacy.php'),'privacy.php');
$privacyBannerLink2Label=trim(setting('privacy_banner_link2_label','Подробнее о cookie'));
$privacyBannerLink2Url=$privacyBannerUrl(setting('privacy_banner_link2_url','cookies.php'),'cookies.php');
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
            <?php $socialAsset=social_service_asset((string)$social['service'],'white'); ?>
            <?php if($socialAsset!==''):?>
              <img class="social-service-image" src="<?=e(base_url($socialAsset))?>" alt="" aria-hidden="true">
            <?php else:?>
              <i class="<?=e(social_service_icon($social['service']))?>" aria-hidden="true"></i>
            <?php endif;?>
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

<?php if($privacyBannerEnabled):?>
<div class="privacy-notice"
     data-privacy-notice
     data-privacy-version="<?=e($privacyBannerVersion)?>"
     hidden
     role="dialog"
     aria-live="polite"
     aria-label="<?=e($privacyBannerTitle!==''?$privacyBannerTitle:'Уведомление о конфиденциальности')?>"
     style="--privacy-bg:<?=e($privacyBannerColor('privacy_banner_bg_color','#fcf8f0'))?>;--privacy-title:<?=e($privacyBannerColor('privacy_banner_title_color','#2f2924'))?>;--privacy-text:<?=e($privacyBannerColor('privacy_banner_text_color','#65594e'))?>;--privacy-accent:<?=e($privacyBannerColor('privacy_banner_accent_color','#80532c'))?>;--privacy-button-bg:<?=e($privacyBannerColor('privacy_banner_button_bg','#6f4a29'))?>;--privacy-button-text:<?=e($privacyBannerColor('privacy_banner_button_text','#ffffff'))?>">
  <div class="privacy-notice-card">
    <div class="privacy-notice-icon" aria-hidden="true">
      <svg viewBox="0 0 24 24"><path d="M12 3l7 3v5c0 4.7-2.7 8.2-7 10-4.3-1.8-7-5.3-7-10V6l7-3z"></path><path d="M9.3 12.1l1.8 1.8 3.8-4"></path></svg>
    </div>
    <div class="privacy-notice-copy">
      <?php if($privacyBannerTitle!==''):?><strong><?=e($privacyBannerTitle)?></strong><?php endif;?>
      <?php if($privacyBannerText!==''):?><p><?=e($privacyBannerText)?></p><?php endif;?>
      <?php if($privacyBannerLink1Label!=='' || $privacyBannerLink2Label!==''):?>
        <div class="privacy-notice-links">
          <?php if($privacyBannerLink1Label!==''):?><a href="<?=e($privacyBannerLink1Url)?>"><?=e($privacyBannerLink1Label)?></a><?php endif;?>
          <?php if($privacyBannerLink2Label!==''):?><a href="<?=e($privacyBannerLink2Url)?>"><?=e($privacyBannerLink2Label)?></a><?php endif;?>
        </div>
      <?php endif;?>
    </div>
    <div class="privacy-notice-actions">
      <button type="button" class="privacy-notice-accept" data-privacy-accept><?=e($privacyBannerAccept)?></button>
    </div>
  </div>
</div>
<?php endif;?>

<script src="<?=e(base_url('assets/js/app.js?v=20261002-accessibility2'))?>"></script>
</div>
</body>
</html>
