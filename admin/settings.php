<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$error='';
$textKeys=['site_name','site_subtitle','hero_kicker','editor_note','footer_quote','topbar_region_label'];
$pdTextKeys=['pd_operator_name','pd_operator_address','pd_operator_email','pd_operator_phone','pd_responsible_person','pd_rkn_registry_number','pd_database_location','pd_policy_approved_date'];

$brandDefaults=[
  'site_favicon'=>'',
  'site_header_logo'=>'',
  'site_footer_logo'=>'',
  'admin_logo'=>'',
];

if($_SERVER['REQUEST_METHOD']==='POST'){
  verify_csrf();

  try{
    // Validate legal details before changing other site settings.
    $pdValues=[];
    foreach($pdTextKeys as $k){
      $value=trim((string)($_POST[$k]??''));
      if($k==='pd_operator_email' && $value!=='' && !filter_var($value,FILTER_VALIDATE_EMAIL)){
        throw new RuntimeException('Укажите корректный e-mail оператора персональных данных.');
      }
      if($k==='pd_policy_approved_date' && $value!==''){
        $date=DateTimeImmutable::createFromFormat('!Y-m-d',$value);
        if(!$date || $date->format('Y-m-d')!==$value){
          throw new RuntimeException('Укажите корректную дату утверждения политики.');
        }
      }
      $pdValues[$k]=$value;
    }
    foreach($pdValues as $k=>$value) save_setting($k,$value);
    foreach($textKeys as $k){
      $value=($k==='editor_note') ? sanitize_rich_text($_POST[$k]??'') : trim($_POST[$k]??'');
      save_setting($k,$value);
    }

    $theme=in_array($_POST['admin_color_scheme']??'walnut',['walnut','light','graphite','forest','burgundy','navy'],true)
      ? $_POST['admin_color_scheme']
      : 'walnut';
    save_setting('admin_color_scheme',$theme);

    $assetMap=[
      'site_favicon'=>['field'=>'site_favicon','kind'=>'favicon'],
      'site_header_logo'=>['field'=>'site_header_logo','kind'=>'logo'],
      'site_footer_logo'=>['field'=>'site_footer_logo','kind'=>'logo'],
      'admin_logo'=>['field'=>'admin_logo','kind'=>'logo'],
    ];

    foreach($assetMap as $key=>$meta){
      $old=setting($key,'');
      if(isset($_POST['reset_'.$key])){
        safe_delete_branding_asset($old);
        save_setting($key,'');
        continue;
      }

      $new=handle_branding_asset_upload($_FILES[$meta['field']]??[],$meta['kind'],$old ?: null) ?? '';
      save_setting($key,$new);
    }

    header('Location: '.base_url('admin/settings.php?saved=1'));
    exit;
  }catch(Throwable $e){
    $error=$e->getMessage();
  }
}

$theme=admin_theme_name();
$favicon=branding_asset('site_favicon','assets/img/seal.svg');
$headerLogo=branding_asset('site_header_logo','assets/img/akhikhan-logo-transparent.webp');
$footerLogo=branding_asset('site_footer_logo','assets/img/akhikhan-logo-hq.webp');
$adminLogo=branding_asset('admin_logo','assets/img/akhikhan-logo-transparent.webp');

$adminTitle='Настройки сайта';
require __DIR__.'/_top.php';
?>

<?php if($error):?><div class="error"><?=e($error)?></div><?php endif;?>
<?php if(isset($_GET['saved'])):?><div class="ok">Настройки и оформление сохранены.</div><?php endif;?>

<form method="post" enctype="multipart/form-data" class="settings-modern">
  <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">

  <div class="settings-page-head">
    <div>
      <span class="editor-eyebrow">Система и бренд</span>
      <h2>Настройки сайта</h2>
      <p>Управляйте основными текстами, логотипами, favicon и цветовой схемой редакционной панели.</p>
    </div>
    <button class="primary settings-save-top" type="submit">Сохранить настройки</button>
  </div>

  <div class="settings-grid">
    <div class="settings-main-column">
      <section class="editor-card settings-section-card">
        <div class="settings-section-head">
          <span class="settings-section-icon">Aa</span>
          <div>
            <h3>Основные тексты</h3>
            <p>Название издания и редакционные подписи.</p>
          </div>
        </div>

        <div class="settings-two-col">
          <label class="field-modern compact"><span>Название сайта</span><input name="site_name" value="<?=e(setting('site_name'))?>"></label>
          <label class="field-modern compact"><span>Подзаголовок</span><input name="site_subtitle" value="<?=e(setting('site_subtitle'))?>"></label>
        </div>

        <label class="field-modern compact"><span>Подпись над главной новостью</span><input name="hero_kicker" value="<?=e(setting('hero_kicker'))?>"></label>
        <label class="field-modern"><span>Слово редактора</span><textarea name="editor_note" rows="6" data-rich-text><?=e(setting('editor_note'))?></textarea></label>
        <label class="field-modern compact"><span>Цитата внизу сайта</span><input name="footer_quote" value="<?=e(setting('footer_quote'))?>"></label>
      </section>

      <section class="editor-card settings-section-card">
        <div class="settings-section-head">
          <span class="settings-section-icon">⌁</span>
          <div>
            <h3>Верхняя панель и контакты</h3>
            <p>Район, социальные сети и публичная почта в компактном top bar над главным меню.</p>
          </div>
        </div>

        <label class="field-modern compact">
          <span>Название района</span>
          <input name="topbar_region_label" maxlength="120" value="<?=e(setting('topbar_region_label','Унцукульский район'))?>">
        </label>

        <div class="settings-socials-link">
          <div>
            <b>Социальные сети и почта</b>
            <span>VK, Одноклассники, MAX, Дзен, Rutube, Telegram и другие ссылки теперь управляются отдельно.</span>
          </div>
          <a href="<?=e(base_url('admin/social-links.php'))?>">Открыть «Мы в соцсетях» →</a>
        </div>
      </section>

      <section class="editor-card settings-section-card" id="personal-data-settings">
        <div class="settings-section-head">
          <span class="settings-section-icon">§</span>
          <div>
            <h3>Персональные данные · 152-ФЗ</h3>
            <p>Подтверждённые сведения оператора для публичной политики. Не указывайте предполагаемые реквизиты.</p>
          </div>
        </div>
        <div class="settings-two-col">
          <label class="field-modern compact"><span>Полное наименование оператора</span><input name="pd_operator_name" maxlength="255" value="<?=e(setting('pd_operator_name',''))?>" placeholder="Юридическое лицо или ИП"></label>
          <label class="field-modern compact"><span>E-mail для запросов по персональным данным</span><input type="email" name="pd_operator_email" maxlength="190" value="<?=e(setting('pd_operator_email',''))?>" placeholder="Контакт для обращений"></label>
        </div>
        <label class="field-modern compact"><span>Адрес оператора</span><input name="pd_operator_address" maxlength="500" value="<?=e(setting('pd_operator_address',''))?>" placeholder="Официальный адрес оператора"></label>
        <div class="settings-two-col">
          <label class="field-modern compact"><span>Телефон (необязательно)</span><input name="pd_operator_phone" maxlength="80" value="<?=e(setting('pd_operator_phone',''))?>"></label>
          <label class="field-modern compact"><span>Ответственный за обработку ПД (если назначен)</span><input name="pd_responsible_person" maxlength="190" value="<?=e(setting('pd_responsible_person',''))?>"></label>
        </div>
        <div class="settings-two-col">
          <label class="field-modern compact"><span>Номер в реестре РКН (если применимо)</span><input name="pd_rkn_registry_number" maxlength="90" value="<?=e(setting('pd_rkn_registry_number',''))?>"></label>
          <label class="field-modern compact"><span>Дата утверждения политики</span><input type="date" name="pd_policy_approved_date" value="<?=e(setting('pd_policy_approved_date',''))?>"></label>
        </div>
        <label class="field-modern compact"><span>Подтверждённое место размещения базы данных граждан РФ</span><input name="pd_database_location" maxlength="190" value="<?=e(setting('pd_database_location',''))?>" placeholder="Уточните фактическую страну и инфраструктуру у хостинга"></label>
        <p class="settings-hint-box">Сведения будут опубликованы в <a href="<?=e(base_url('privacy.php'))?>" target="_blank" rel="noopener">Политике обработки персональных данных</a>. Заполнение формы не заменяет проверку законности обработки, локализации, уведомлений РКН и внешних сервисов.</p>
      </section>

      <section class="editor-card settings-section-card">
        <div class="settings-section-head">
          <span class="settings-section-icon">◈</span>
          <div>
            <h3>Логотипы и favicon</h3>
            <p>Каждая область сайта может использовать свой вариант логотипа.</p>
          </div>
        </div>

        <div class="branding-assets-grid">
          <div class="branding-asset-card">
            <div class="branding-preview favicon-preview"><img src="<?=e(base_url($favicon))?>" alt="Текущий favicon"></div>
            <div class="branding-copy"><h4>Favicon сайта</h4><p>Значок вкладки браузера. Лучше квадратный файл.</p></div>
            <label class="branding-upload"><input type="file" name="site_favicon" accept=".ico,image/png,image/jpeg,image/webp"><span>Выбрать favicon</span></label>
            <?php if(setting('site_favicon','')!==''):?><label class="branding-reset"><input type="checkbox" name="reset_site_favicon" value="1"> Вернуть стандартный</label><?php endif;?>
          </div>

          <div class="branding-asset-card">
            <div class="branding-preview logo-preview light"><img src="<?=e(base_url($headerLogo))?>" alt="Логотип шапки"></div>
            <div class="branding-copy"><h4>Логотип сверху</h4><p>Большой логотип в шапке главной страницы.</p></div>
            <label class="branding-upload"><input type="file" name="site_header_logo" accept="image/png,image/jpeg,image/webp"><span>Загрузить логотип</span></label>
            <?php if(setting('site_header_logo','')!==''):?><label class="branding-reset"><input type="checkbox" name="reset_site_header_logo" value="1"> Вернуть стандартный</label><?php endif;?>
          </div>

          <div class="branding-asset-card">
            <div class="branding-preview logo-preview dark"><img src="<?=e(base_url($footerLogo))?>" alt="Логотип подвала"></div>
            <div class="branding-copy"><h4>Логотип снизу</h4><p>Логотип в нижней части сайта — в подвале.</p></div>
            <label class="branding-upload"><input type="file" name="site_footer_logo" accept="image/png,image/jpeg,image/webp"><span>Загрузить логотип</span></label>
            <?php if(setting('site_footer_logo','')!==''):?><label class="branding-reset"><input type="checkbox" name="reset_site_footer_logo" value="1"> Вернуть стандартный</label><?php endif;?>
          </div>

          <div class="branding-asset-card">
            <div class="branding-preview logo-preview admin"><img src="<?=e(base_url($adminLogo))?>" alt="Логотип админки"></div>
            <div class="branding-copy"><h4>Логотип админки</h4><p>Используется в боковой панели и на странице входа.</p></div>
            <label class="branding-upload"><input type="file" name="admin_logo" accept="image/png,image/jpeg,image/webp"><span>Загрузить логотип</span></label>
            <?php if(setting('admin_logo','')!==''):?><label class="branding-reset"><input type="checkbox" name="reset_admin_logo" value="1"> Вернуть стандартный</label><?php endif;?>
          </div>
        </div>
      </section>

      <section class="editor-card settings-section-card">
        <div class="settings-section-head">
          <span class="settings-section-icon">●</span>
          <div>
            <h3>Цветовая схема админки</h3>
            <p>Выберите готовую палитру. Меняются фон, боковая панель, акценты, кнопки и активные элементы.</p>
          </div>
        </div>

        <div class="theme-picker">
          <?php
          $themes=[
            'walnut'=>['Орех','Тёплая фирменная схема',['#211b17','#b78951','#f4f2ee']],
            'light'=>['Светлая','Светлая панель с тёплыми акцентами',['#f7f3ed','#a97843','#ffffff']],
            'graphite'=>['Графит','Строгая нейтральная схема',['#202225','#9299a1','#f2f3f4']],
            'forest'=>['Лес','Спокойные зелёные акценты',['#17231d','#65876e','#f1f4f1']],
            'burgundy'=>['Бордо','Глубокая редакционная палитра',['#28171a','#a65d68','#f6f1f2']],
            'navy'=>['Синий','Современная холодная схема',['#171f2d','#5f7fa9','#f1f4f8']],
          ];
          foreach($themes as $key=>$meta):
          ?>
            <label class="theme-option <?=$theme===$key?'is-selected':''?>" data-admin-theme-option="<?=e($key)?>">
              <input type="radio" name="admin_color_scheme" value="<?=e($key)?>" <?=$theme===$key?'checked':''?>>
              <span class="theme-swatches">
                <?php foreach($meta[2] as $color):?><i style="background:<?=e($color)?>"></i><?php endforeach;?>
              </span>
              <span class="theme-option-copy"><b><?=e($meta[0])?></b><small><?=e($meta[1])?></small></span>
              <span class="theme-check">✓</span>
            </label>
          <?php endforeach;?>
        </div>
      </section>
    </div>

    <aside class="editor-card settings-preview-card">
      <div class="side-card-title">
        <span class="side-icon">◉</span>
        <div><h3>Как используется бренд</h3><p>Четыре независимых файла</p></div>
      </div>

      <div class="settings-brand-map">
        <div><span>01</span><b>Favicon</b><small>Вкладка браузера</small></div>
        <div><span>02</span><b>Шапка сайта</b><small>Верх главной страницы</small></div>
        <div><span>03</span><b>Подвал сайта</b><small>Нижняя часть страниц</small></div>
        <div><span>04</span><b>Админка</b><small>Панель и вход редакции</small></div>
      </div>

      <div class="settings-hint-box">
        <b>Рекомендация</b>
        <p>Для логотипов лучше использовать PNG или WEBP с прозрачным фоном. Favicon — квадратный PNG/ICO не меньше 64×64.</p>
      </div>
    </aside>
  </div>

  <div class="settings-bottom-save">
    <button class="primary" type="submit">Сохранить все изменения</button>
  </div>
</form>

<script>
(function(){
  const options=[...document.querySelectorAll('[data-admin-theme-option]')];
  if(!options.length) return;
  const themeClasses=['admin-theme-walnut','admin-theme-light','admin-theme-graphite','admin-theme-forest','admin-theme-burgundy','admin-theme-navy'];

  function previewAdminTheme(theme){
    document.body.classList.remove(...themeClasses);
    document.body.classList.add('admin-theme-'+theme);
    options.forEach(option=>{
      option.classList.toggle('is-selected', option.dataset.adminThemeOption===theme);
    });
  }

  options.forEach(option=>{
    const input=option.querySelector('input[type="radio"]');
    if(!input) return;
    input.addEventListener('change',()=>{
      if(input.checked) previewAdminTheme(input.value);
    });
  });
})();
</script>

<?php require __DIR__.'/_bottom.php'; ?>
