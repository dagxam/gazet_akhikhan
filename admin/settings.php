<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$error='';
$textKeys=['site_name','site_subtitle','hero_kicker','editor_note','footer_quote','topbar_region_label'];
$pdTextKeys=['pd_operator_name','pd_operator_address','pd_operator_email','pd_operator_phone','pd_responsible_person','pd_rkn_registry_number','pd_database_location','pd_policy_approved_date'];

$privacyBannerDefaults=[
  'privacy_banner_enabled'=>'1',
  'privacy_banner_title'=>'Cookie и конфиденциальность',
  'privacy_banner_text'=>'Для работы редакционной системы используются необходимые технические cookie. Некоторые страницы также обращаются к внешним сервисам для шрифтов, иконок, погоды и предпросмотра PDF.',
  'privacy_banner_accept_label'=>'Принять',
  'privacy_banner_link1_label'=>'Персональные данные',
  'privacy_banner_link1_url'=>'privacy.php',
  'privacy_banner_link2_label'=>'Подробнее о cookie',
  'privacy_banner_link2_url'=>'cookies.php',
  'privacy_banner_bg_color'=>'#fcf8f0',
  'privacy_banner_title_color'=>'#2f2924',
  'privacy_banner_text_color'=>'#65594e',
  'privacy_banner_accent_color'=>'#80532c',
  'privacy_banner_button_bg'=>'#6f4a29',
  'privacy_banner_button_text'=>'#ffffff',
  'privacy_banner_version'=>'1',
];
$privacyBannerTextKeys=['privacy_banner_title','privacy_banner_text','privacy_banner_accept_label','privacy_banner_link1_label','privacy_banner_link1_url','privacy_banner_link2_label','privacy_banner_link2_url'];
$privacyBannerColorKeys=['privacy_banner_bg_color','privacy_banner_title_color','privacy_banner_text_color','privacy_banner_accent_color','privacy_banner_button_bg','privacy_banner_button_text'];

$brandDefaults=[
  'site_favicon'=>'',
  'site_header_logo'=>'',
  'site_footer_logo'=>'',
  'admin_logo'=>'',
];

$accessibilityDefaults=[
  'accessibility_enabled'=>'1',
  'accessibility_label'=>'Версия для слабовидящих',
  'accessibility_panel_title'=>'Версия для слабовидящих',
  'accessibility_panel_text'=>'Настройте отображение сайта под себя: размер текста, контраст, интервалы, изображения и анимацию.',
  'accessibility_default_font'=>'100',
  'accessibility_default_contrast'=>'normal',
  'accessibility_default_spacing'=>'normal',
  'accessibility_default_grayscale'=>'0',
  'accessibility_default_reduce_motion'=>'1',
  'accessibility_control_font'=>'1',
  'accessibility_control_contrast'=>'1',
  'accessibility_control_spacing'=>'1',
  'accessibility_control_grayscale'=>'1',
  'accessibility_control_motion'=>'1',
  'accessibility_panel_bg'=>'#fffdf9',
  'accessibility_panel_text_color'=>'#302923',
  'accessibility_panel_accent'=>'#765132',
  'accessibility_panel_border'=>'#d8c7b4',
  'accessibility_primary_bg'=>'#5d402a',
  'accessibility_primary_text'=>'#ffffff',
  'age_rating_enabled'=>'1',
  'age_rating_label'=>'16+',
];
$accessibilityTextKeys=['accessibility_label','accessibility_panel_title','accessibility_panel_text'];
$accessibilityColorKeys=['accessibility_panel_bg','accessibility_panel_text_color','accessibility_panel_accent','accessibility_panel_border','accessibility_primary_bg','accessibility_primary_text'];

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

    save_setting('privacy_banner_enabled',!empty($_POST['privacy_banner_enabled']) ? '1' : '0');

    foreach($privacyBannerTextKeys as $k){
      $value=trim((string)($_POST[$k]??$privacyBannerDefaults[$k]));
      $limit=($k==='privacy_banner_text') ? 1200 : (($k==='privacy_banner_link1_url'||$k==='privacy_banner_link2_url') ? 500 : 160);
      if(function_exists('mb_substr')) $value=mb_substr($value,0,$limit,'UTF-8');
      else $value=substr($value,0,$limit);

      if($k==='privacy_banner_link1_url'||$k==='privacy_banner_link2_url'){
        if($value==='' || preg_match('~^(?:https?://|mailto:|tel:|/|[A-Za-z0-9][A-Za-z0-9_./?=&%#-]*)$~u',$value)!==1){
          throw new RuntimeException('Ссылка в баннере персональных данных указана некорректно.');
        }
      }
      save_setting($k,$value);
    }

    foreach($privacyBannerColorKeys as $k){
      $value=trim((string)($_POST[$k]??$privacyBannerDefaults[$k]));
      if(!preg_match('/^#[0-9a-fA-F]{6}$/',$value)){
        throw new RuntimeException('Цвета баннера должны быть указаны в формате #RRGGBB.');
      }
      save_setting($k,strtolower($value));
    }

    $bannerVersion=trim((string)($_POST['privacy_banner_version']??'1'));
    if(!preg_match('/^[A-Za-z0-9._-]{1,40}$/',$bannerVersion)){
      throw new RuntimeException('Версия баннера может содержать только буквы, цифры, точку, дефис и подчёркивание.');
    }
    save_setting('privacy_banner_version',$bannerVersion);

    save_setting('accessibility_enabled',!empty($_POST['accessibility_enabled']) ? '1' : '0');
    save_setting('accessibility_default_reduce_motion',!empty($_POST['accessibility_default_reduce_motion']) ? '1' : '0');
    save_setting('accessibility_default_grayscale',!empty($_POST['accessibility_default_grayscale']) ? '1' : '0');
    save_setting('age_rating_enabled',!empty($_POST['age_rating_enabled']) ? '1' : '0');

    foreach(['accessibility_control_font','accessibility_control_contrast','accessibility_control_spacing','accessibility_control_grayscale','accessibility_control_motion'] as $toggleKey){
      save_setting($toggleKey,!empty($_POST[$toggleKey]) ? '1' : '0');
    }

    foreach($accessibilityTextKeys as $k){
      $value=trim((string)($_POST[$k]??$accessibilityDefaults[$k]));
      $limit=$k==='accessibility_panel_text' ? 500 : 120;
      if(function_exists('mb_substr')) $value=mb_substr($value,0,$limit,'UTF-8');
      else $value=substr($value,0,$limit);
      if($value==='') $value=$accessibilityDefaults[$k];
      save_setting($k,$value);
    }

    foreach($accessibilityColorKeys as $k){
      $value=trim((string)($_POST[$k]??$accessibilityDefaults[$k]));
      if(!preg_match('/^#[0-9a-fA-F]{6}$/',$value)){
        throw new RuntimeException('Цвета версии для слабовидящих должны быть указаны в формате #RRGGBB.');
      }
      save_setting($k,strtolower($value));
    }

    $accessibilityFont=(string)($_POST['accessibility_default_font']??$accessibilityDefaults['accessibility_default_font']);
    if(!in_array($accessibilityFont,['100','125','150','200'],true)){
      throw new RuntimeException('Некорректный размер текста для версии повышенной доступности.');
    }
    save_setting('accessibility_default_font',$accessibilityFont);

    $accessibilityContrast=(string)($_POST['accessibility_default_contrast']??$accessibilityDefaults['accessibility_default_contrast']);
    if(!in_array($accessibilityContrast,['normal','black-white','white-black','yellow-black'],true)){
      throw new RuntimeException('Некорректная контрастная схема.');
    }
    save_setting('accessibility_default_contrast',$accessibilityContrast);

    $accessibilitySpacing=(string)($_POST['accessibility_default_spacing']??$accessibilityDefaults['accessibility_default_spacing']);
    if(!in_array($accessibilitySpacing,['normal','wide'],true)){
      throw new RuntimeException('Некорректная настройка интервалов.');
    }
    save_setting('accessibility_default_spacing',$accessibilitySpacing);

    $ageRating=(string)($_POST['age_rating_label']??$accessibilityDefaults['age_rating_label']);
    if(!in_array($ageRating,['0+','6+','12+','16+','18+'],true)){
      throw new RuntimeException('Возрастная маркировка должна быть 0+, 6+, 12+, 16+ или 18+.');
    }
    save_setting('age_rating_label',$ageRating);

    if(isset($_POST['menu_order']) && is_array($_POST['menu_order'])){
      save_main_menu_order($_POST['menu_order']);
    }

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

$privacyBanner=[];
foreach($privacyBannerDefaults as $key=>$default){
  $privacyBanner[$key]=setting($key,$default);
}

$accessibilitySettings=[];
foreach($accessibilityDefaults as $key=>$default){
  $accessibilitySettings[$key]=setting($key,$default);
}
$settingsMenuItems=main_menu_items(false);

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

      <section class="editor-card settings-section-card" id="main-menu-settings">
        <div class="settings-section-head">
          <span class="settings-section-icon"><i class="fa-solid fa-bars-staggered" aria-hidden="true"></i></span>
          <div>
            <h3>Главное меню</h3>
            <p>Меняйте порядок пунктов прямо здесь. Перетащите строку за ручку или используйте стрелки.</p>
          </div>
        </div>

        <?php if($settingsMenuItems):?>
          <div class="settings-menu-toolbar">
            <div>
              <b>Порядок в шапке сайта</b>
              <span>Первый пункт будет слева. Неактивные пункты остаются в списке, но не показываются посетителям.</span>
            </div>
            <a href="<?=e(base_url('admin/main-menu.php'))?>">Добавить или изменить пункт →</a>
          </div>

          <div class="settings-menu-sortable" data-settings-menu-sortable>
            <?php foreach($settingsMenuItems as $index=>$menuItem):?>
              <article class="settings-menu-item" draggable="false" data-menu-sort-item>
                <input type="hidden" name="menu_order[]" value="<?=e((string)$menuItem['id'])?>">
                <button class="settings-menu-drag" type="button" data-menu-drag aria-label="Перетащить пункт <?=e($menuItem['label'])?>">
                  <i class="fa-solid fa-grip-vertical" aria-hidden="true"></i>
                </button>
                <span class="settings-menu-number" data-menu-number><?=e(str_pad((string)($index+1),2,'0',STR_PAD_LEFT))?></span>
                <span class="settings-menu-copy">
                  <b><?=e($menuItem['label'])?></b>
                  <small><?=e($menuItem['url'])?></small>
                </span>
                <span class="settings-menu-state <?=empty($menuItem['is_active'])?'is-off':''?>">
                  <?=empty($menuItem['is_active'])?'Скрыт':'На сайте'?>
                </span>
                <span class="settings-menu-move">
                  <button type="button" data-menu-move="up" aria-label="Поднять <?=e($menuItem['label'])?> выше"><i class="fa-solid fa-chevron-up"></i></button>
                  <button type="button" data-menu-move="down" aria-label="Опустить <?=e($menuItem['label'])?> ниже"><i class="fa-solid fa-chevron-down"></i></button>
                </span>
              </article>
            <?php endforeach;?>
          </div>

          <div class="settings-menu-preview">
            <span>Предпросмотр</span>
            <div data-menu-preview>
              <?php foreach($settingsMenuItems as $menuItem): if(empty($menuItem['is_active'])) continue;?>
                <b data-menu-preview-id="<?=e((string)$menuItem['id'])?>"><?=e($menuItem['label'])?></b>
              <?php endforeach;?>
            </div>
          </div>
        <?php else:?>
          <div class="settings-hint-box">Пункты меню ещё не созданы. <a href="<?=e(base_url('admin/main-menu.php'))?>">Создать главное меню →</a></div>
        <?php endif;?>
      </section>

      <section class="editor-card settings-section-card accessibility-settings-pro" id="accessibility-settings">
        <div class="settings-section-head">
          <span class="settings-section-icon"><i class="fa-solid fa-universal-access" aria-hidden="true"></i></span>
          <div>
            <h3>Версия для слабовидящих</h3>
            <p>Полная настройка режима повышенной доступности: содержимое, функции, стартовые параметры и оформление панели.</p>
          </div>
        </div>

        <div class="accessibility-admin-hero">
          <div class="accessibility-admin-hero-icon"><i class="fa-regular fa-eye"></i></div>
          <div>
            <span class="editor-eyebrow">Доступность сайта</span>
            <h4>Настройте режим так, как должен видеть его посетитель</h4>
            <p>Пользователь сможет увеличить текст, выбрать контраст, увеличить интервалы, обесцветить изображения и отключить анимацию. Его выбор сохраняется в браузере.</p>
          </div>
          <label class="accessibility-master-switch">
            <input type="checkbox" name="accessibility_enabled" value="1" <?=$accessibilitySettings['accessibility_enabled']==='1'?'checked':''?>>
            <span></span>
            <b>Включено</b>
          </label>
        </div>

        <div class="accessibility-admin-tabs">
          <div class="accessibility-admin-block">
            <div class="accessibility-admin-block-head">
              <span>01</span>
              <div><b>Тексты панели</b><small>Название кнопки и пояснение для посетителя</small></div>
            </div>
            <div class="settings-two-col">
              <label class="field-modern compact">
                <span>Кнопка в верхней панели</span>
                <input name="accessibility_label" maxlength="120" value="<?=e($accessibilitySettings['accessibility_label'])?>" data-a11y-admin-text="button">
              </label>
              <label class="field-modern compact">
                <span>Заголовок окна</span>
                <input name="accessibility_panel_title" maxlength="120" value="<?=e($accessibilitySettings['accessibility_panel_title'])?>" data-a11y-admin-text="title">
              </label>
            </div>
            <label class="field-modern compact">
              <span>Короткое пояснение</span>
              <textarea name="accessibility_panel_text" rows="3" maxlength="500" data-a11y-admin-text="text"><?=e($accessibilitySettings['accessibility_panel_text'])?></textarea>
            </label>
          </div>

          <div class="accessibility-admin-block">
            <div class="accessibility-admin-block-head">
              <span>02</span>
              <div><b>Какие инструменты показывать</b><small>Отключите функции, которые не хотите выводить в пользовательской панели</small></div>
            </div>
            <div class="accessibility-feature-grid">
              <?php
              $a11yFeatures=[
                'accessibility_control_font'=>['fa-font','Размер текста','100–200%'],
                'accessibility_control_contrast'=>['fa-circle-half-stroke','Контраст','4 цветовые схемы'],
                'accessibility_control_spacing'=>['fa-text-width','Интервалы','обычные / увеличенные'],
                'accessibility_control_grayscale'=>['fa-image','Изображения','цветные / чёрно-белые'],
                'accessibility_control_motion'=>['fa-person-walking','Анимация','обычная / минимальная'],
              ];
              foreach($a11yFeatures as $key=>$meta):
              ?>
                <label class="accessibility-feature-card">
                  <input type="checkbox" name="<?=e($key)?>" value="1" <?=$accessibilitySettings[$key]==='1'?'checked':''?>>
                  <span class="accessibility-feature-icon"><i class="fa-solid <?=e($meta[0])?>"></i></span>
                  <span><b><?=e($meta[1])?></b><small><?=e($meta[2])?></small></span>
                  <i class="accessibility-feature-check fa-solid fa-check"></i>
                </label>
              <?php endforeach;?>
            </div>
          </div>

          <div class="accessibility-admin-block">
            <div class="accessibility-admin-block-head">
              <span>03</span>
              <div><b>Настройки при первом включении</b><small>Стартовое состояние, которое посетитель затем может изменить</small></div>
            </div>
            <div class="settings-two-col">
              <label class="field-modern compact">
                <span>Размер текста</span>
                <select name="accessibility_default_font">
                  <?php foreach(['100'=>'100%','125'=>'125%','150'=>'150%','200'=>'200%'] as $value=>$label):?>
                    <option value="<?=e($value)?>" <?=$accessibilitySettings['accessibility_default_font']===$value?'selected':''?>><?=e($label)?></option>
                  <?php endforeach;?>
                </select>
              </label>
              <label class="field-modern compact">
                <span>Контраст</span>
                <select name="accessibility_default_contrast">
                  <option value="normal" <?=$accessibilitySettings['accessibility_default_contrast']==='normal'?'selected':''?>>Обычный</option>
                  <option value="black-white" <?=$accessibilitySettings['accessibility_default_contrast']==='black-white'?'selected':''?>>Чёрный текст / белый фон</option>
                  <option value="white-black" <?=$accessibilitySettings['accessibility_default_contrast']==='white-black'?'selected':''?>>Белый текст / чёрный фон</option>
                  <option value="yellow-black" <?=$accessibilitySettings['accessibility_default_contrast']==='yellow-black'?'selected':''?>>Жёлтый текст / чёрный фон</option>
                </select>
              </label>
            </div>
            <div class="settings-two-col">
              <label class="field-modern compact">
                <span>Интервалы</span>
                <select name="accessibility_default_spacing">
                  <option value="normal" <?=$accessibilitySettings['accessibility_default_spacing']==='normal'?'selected':''?>>Обычные</option>
                  <option value="wide" <?=$accessibilitySettings['accessibility_default_spacing']==='wide'?'selected':''?>>Увеличенные</option>
                </select>
              </label>
              <div class="accessibility-default-switches">
                <label class="featured-switch">
                  <input type="checkbox" name="accessibility_default_grayscale" value="1" <?=$accessibilitySettings['accessibility_default_grayscale']==='1'?'checked':''?>>
                  <span class="switch-ui"></span><span><b>Ч/б изображения</b><small>Включить при первом запуске</small></span>
                </label>
                <label class="featured-switch">
                  <input type="checkbox" name="accessibility_default_reduce_motion" value="1" <?=$accessibilitySettings['accessibility_default_reduce_motion']==='1'?'checked':''?>>
                  <span class="switch-ui"></span><span><b>Минимум анимации</b><small>Рекомендуемый спокойный режим</small></span>
                </label>
              </div>
            </div>
          </div>

          <div class="accessibility-admin-block">
            <div class="accessibility-admin-block-head">
              <span>04</span>
              <div><b>Оформление панели</b><small>Цвета можно точно подстроить под фирменный стиль</small></div>
            </div>
            <div class="accessibility-color-grid">
              <?php
              $a11yColors=[
                'accessibility_panel_bg'=>['Фон панели','panel-bg'],
                'accessibility_panel_text_color'=>['Основной текст','panel-text'],
                'accessibility_panel_accent'=>['Акцент','accent'],
                'accessibility_panel_border'=>['Рамки','border'],
                'accessibility_primary_bg'=>['Главная кнопка','primary-bg'],
                'accessibility_primary_text'=>['Текст кнопки','primary-text'],
              ];
              foreach($a11yColors as $key=>$meta):
              ?>
                <label class="accessibility-color-field">
                  <span><?=e($meta[0])?></span>
                  <span class="accessibility-color-control">
                    <input type="color" name="<?=e($key)?>" value="<?=e($accessibilitySettings[$key])?>" data-a11y-admin-color="<?=e($meta[1])?>">
                    <code><?=e($accessibilitySettings[$key])?></code>
                  </span>
                </label>
              <?php endforeach;?>
            </div>
          </div>
        </div>

        <div class="accessibility-admin-preview"
             data-a11y-admin-preview
             style="--ap-bg:<?=e($accessibilitySettings['accessibility_panel_bg'])?>;--ap-text:<?=e($accessibilitySettings['accessibility_panel_text_color'])?>;--ap-accent:<?=e($accessibilitySettings['accessibility_panel_accent'])?>;--ap-border:<?=e($accessibilitySettings['accessibility_panel_border'])?>;--ap-primary:<?=e($accessibilitySettings['accessibility_primary_bg'])?>;--ap-primary-text:<?=e($accessibilitySettings['accessibility_primary_text'])?>">
          <div class="accessibility-admin-preview-trigger">
            <span><i class="fa-regular fa-eye"></i></span>
            <b data-a11y-admin-preview-button><?=e($accessibilitySettings['accessibility_label'])?></b>
            <small>Вкл.</small>
          </div>
          <div class="accessibility-admin-preview-top">
            <span class="accessibility-admin-preview-eye"><i class="fa-regular fa-eye"></i></span>
            <div>
              <small>Предпросмотр</small>
              <strong data-a11y-admin-preview-title><?=e($accessibilitySettings['accessibility_panel_title'])?></strong>
              <p data-a11y-admin-preview-text><?=e($accessibilitySettings['accessibility_panel_text'])?></p>
            </div>
            <span class="accessibility-admin-preview-close">×</span>
          </div>
          <div class="accessibility-admin-preview-controls">
            <span>A 100%</span><span class="is-active">A 150%</span><span>◐ Контраст</span><span>↔ Интервалы</span>
          </div>
          <div class="accessibility-admin-preview-actions">
            <button type="button">Сбросить</button>
            <button class="is-primary" type="button">Обычная версия сайта</button>
          </div>
        </div>

        <div class="accessibility-admin-divider"></div>

        <div class="accessibility-age-settings">
          <div>
            <span class="heading-kicker">Возрастная маркировка</span>
            <h4>Ограничение информационной продукции</h4>
            <p>Отдельная настройка знака в верхней панели. Выберите категорию, соответствующую фактической классификации материалов редакцией.</p>
          </div>
          <div class="accessibility-age-controls">
            <label class="featured-switch">
              <input type="checkbox" name="age_rating_enabled" value="1" <?=$accessibilitySettings['age_rating_enabled']==='1'?'checked':''?>>
              <span class="switch-ui"></span>
              <span><b>Показывать возрастной знак</b><small>Рядом с кнопкой доступности</small></span>
            </label>
            <label class="field-modern compact">
              <span>Категория</span>
              <select name="age_rating_label">
                <?php foreach(['0+','6+','12+','16+','18+'] as $age):?>
                  <option value="<?=e($age)?>" <?=$accessibilitySettings['age_rating_label']===$age?'selected':''?>><?=e($age)?></option>
                <?php endforeach;?>
              </select>
            </label>
          </div>
        </div>

        <div class="settings-hint-box accessibility-law-note">
          <b>Контроль доступности</b>
          <p>Сам режим помогает посетителю изменить отображение. Отдельно продолжайте проверять alt-тексты изображений, понятные подписи ссылок и кнопок, доступность форм, видео и загружаемых документов.</p>
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

      <section class="editor-card settings-section-card" id="privacy-banner-settings">
        <div class="settings-section-head">
          <span class="settings-section-icon">▰</span>
          <div>
            <h3>Баннер персональных данных</h3>
            <p>Настройте нижнее уведомление: тексты, ссылки, кнопку и фирменные цвета.</p>
          </div>
        </div>

        <label class="featured-switch privacy-banner-toggle">
          <input type="checkbox" name="privacy_banner_enabled" value="1" <?=$privacyBanner['privacy_banner_enabled']==='1'?'checked':''?>>
          <span class="switch-ui"></span>
          <span><b>Показывать баннер на публичных страницах</b><small>Если выключить, уведомление полностью исчезнет с сайта.</small></span>
        </label>

        <div class="settings-two-col">
          <label class="field-modern compact"><span>Заголовок</span><input name="privacy_banner_title" maxlength="160" value="<?=e($privacyBanner['privacy_banner_title'])?>" data-privacy-preview-field="title"></label>
          <label class="field-modern compact"><span>Текст кнопки</span><input name="privacy_banner_accept_label" maxlength="160" value="<?=e($privacyBanner['privacy_banner_accept_label'])?>" data-privacy-preview-field="button"></label>
        </div>

        <label class="field-modern compact"><span>Основной текст</span><textarea name="privacy_banner_text" rows="4" maxlength="1200" data-privacy-preview-field="text"><?=e($privacyBanner['privacy_banner_text'])?></textarea></label>

        <div class="privacy-banner-link-grid">
          <div class="privacy-banner-link-box">
            <b>Ссылка 1</b>
            <label class="field-modern compact"><span>Текст ссылки</span><input name="privacy_banner_link1_label" maxlength="160" value="<?=e($privacyBanner['privacy_banner_link1_label'])?>" data-privacy-preview-field="link1"></label>
            <label class="field-modern compact"><span>Адрес</span><input name="privacy_banner_link1_url" maxlength="500" value="<?=e($privacyBanner['privacy_banner_link1_url'])?>" placeholder="privacy.php или https://..."></label>
          </div>
          <div class="privacy-banner-link-box">
            <b>Ссылка 2</b>
            <label class="field-modern compact"><span>Текст ссылки</span><input name="privacy_banner_link2_label" maxlength="160" value="<?=e($privacyBanner['privacy_banner_link2_label'])?>" data-privacy-preview-field="link2"></label>
            <label class="field-modern compact"><span>Адрес</span><input name="privacy_banner_link2_url" maxlength="500" value="<?=e($privacyBanner['privacy_banner_link2_url'])?>" placeholder="cookies.php или https://..."></label>
          </div>
        </div>

        <div class="privacy-banner-color-grid">
          <?php
          $bannerColors=[
            'privacy_banner_bg_color'=>['Фон баннера','bg'],
            'privacy_banner_title_color'=>['Цвет заголовка','title-color'],
            'privacy_banner_text_color'=>['Цвет текста','text-color'],
            'privacy_banner_accent_color'=>['Ссылки и акцент','accent'],
            'privacy_banner_button_bg'=>['Фон кнопки','button-bg'],
            'privacy_banner_button_text'=>['Текст кнопки','button-text'],
          ];
          foreach($bannerColors as $key=>$meta):
          ?>
            <label class="privacy-banner-color-field">
              <span><?=e($meta[0])?></span>
              <span class="privacy-banner-color-control">
                <input type="color" name="<?=e($key)?>" value="<?=e($privacyBanner[$key])?>" data-privacy-preview-color="<?=e($meta[1])?>">
                <code><?=e($privacyBanner[$key])?></code>
              </span>
            </label>
          <?php endforeach;?>
        </div>

        <div class="settings-two-col privacy-banner-meta">
          <label class="field-modern compact">
            <span>Версия уведомления</span>
            <input name="privacy_banner_version" maxlength="40" value="<?=e($privacyBanner['privacy_banner_version'])?>">
            <small>Измените, например, с 1 на 2 — баннер снова появится даже у тех, кто уже нажимал «Принять».</small>
          </label>
          <div class="settings-hint-box privacy-banner-version-note">
            <b>Важно</b>
            <p>Ссылки можно указывать как внутренние, например privacy.php, или как полные https-ссылки. Все тексты выводятся безопасно без HTML.</p>
          </div>
        </div>

        <div class="privacy-banner-admin-preview"
             data-privacy-banner-preview
             style="--pb-bg:<?=e($privacyBanner['privacy_banner_bg_color'])?>;--pb-title:<?=e($privacyBanner['privacy_banner_title_color'])?>;--pb-text:<?=e($privacyBanner['privacy_banner_text_color'])?>;--pb-accent:<?=e($privacyBanner['privacy_banner_accent_color'])?>;--pb-button-bg:<?=e($privacyBanner['privacy_banner_button_bg'])?>;--pb-button-text:<?=e($privacyBanner['privacy_banner_button_text'])?>">
          <div class="privacy-banner-admin-icon">✓</div>
          <div>
            <strong data-privacy-preview-title><?=e($privacyBanner['privacy_banner_title'])?></strong>
            <p data-privacy-preview-text><?=e($privacyBanner['privacy_banner_text'])?></p>
            <span class="privacy-banner-admin-links"><u data-privacy-preview-link1><?=e($privacyBanner['privacy_banner_link1_label'])?></u><u data-privacy-preview-link2><?=e($privacyBanner['privacy_banner_link2_label'])?></u></span>
          </div>
          <button type="button" data-privacy-preview-button><?=e($privacyBanner['privacy_banner_accept_label'])?></button>
        </div>
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

(function(){
  const preview=document.querySelector('[data-privacy-banner-preview]');
  if(!preview) return;

  const textMap={
    title:preview.querySelector('[data-privacy-preview-title]'),
    text:preview.querySelector('[data-privacy-preview-text]'),
    button:preview.querySelector('[data-privacy-preview-button]'),
    link1:preview.querySelector('[data-privacy-preview-link1]'),
    link2:preview.querySelector('[data-privacy-preview-link2]')
  };
  document.querySelectorAll('[data-privacy-preview-field]').forEach(input=>{
    input.addEventListener('input',()=>{
      const target=textMap[input.dataset.privacyPreviewField];
      if(target) target.textContent=input.value;
    });
  });

  const vars={
    'bg':'--pb-bg',
    'title-color':'--pb-title',
    'text-color':'--pb-text',
    'accent':'--pb-accent',
    'button-bg':'--pb-button-bg',
    'button-text':'--pb-button-text'
  };
  document.querySelectorAll('[data-privacy-preview-color]').forEach(input=>{
    const code=input.closest('.privacy-banner-color-control')?.querySelector('code');
    const sync=()=>{
      const cssVar=vars[input.dataset.privacyPreviewColor];
      if(cssVar) preview.style.setProperty(cssVar,input.value);
      if(code) code.textContent=input.value;
    };
    input.addEventListener('input',sync);
    sync();
  });
})();

(function(){
  const list=document.querySelector('[data-settings-menu-sortable]');
  if(!list) return;

  const preview=document.querySelector('[data-menu-preview]');
  let dragging=null;

  function items(){
    return [...list.querySelectorAll('[data-menu-sort-item]')];
  }

  function sync(){
    items().forEach((item,index)=>{
      const number=item.querySelector('[data-menu-number]');
      if(number) number.textContent=String(index+1).padStart(2,'0');
      const up=item.querySelector('[data-menu-move="up"]');
      const down=item.querySelector('[data-menu-move="down"]');
      if(up) up.disabled=index===0;
      if(down) down.disabled=index===items().length-1;
    });

    if(preview){
      const previewMap=new Map(
        [...preview.querySelectorAll('[data-menu-preview-id]')].map(el=>[el.dataset.menuPreviewId,el])
      );
      items().forEach(item=>{
        const id=item.querySelector('input[name="menu_order[]"]')?.value||'';
        const previewItem=previewMap.get(id);
        if(previewItem) preview.appendChild(previewItem);
      });
    }
  }

  items().forEach(item=>{
    const drag=item.querySelector('[data-menu-drag]');
    if(drag){
      drag.addEventListener('mousedown',()=>item.setAttribute('draggable','true'));
      drag.addEventListener('touchstart',()=>item.setAttribute('draggable','true'),{passive:true});
      drag.addEventListener('mouseup',()=>item.setAttribute('draggable','false'));
      drag.addEventListener('touchend',()=>item.setAttribute('draggable','false'));
    }

    item.addEventListener('dragstart',event=>{
      dragging=item;
      item.classList.add('is-dragging');
      event.dataTransfer.effectAllowed='move';
      try{event.dataTransfer.setData('text/plain',item.querySelector('input[name="menu_order[]"]')?.value||'');}catch(e){}
    });

    item.addEventListener('dragend',()=>{
      item.classList.remove('is-dragging');
      item.setAttribute('draggable','false');
      items().forEach(row=>row.classList.remove('is-drag-over'));
      dragging=null;
      sync();
    });

    item.addEventListener('dragover',event=>{
      if(!dragging||dragging===item) return;
      event.preventDefault();
      item.classList.add('is-drag-over');
      const rect=item.getBoundingClientRect();
      const after=event.clientY>rect.top+rect.height/2;
      if(after) item.after(dragging);
      else item.before(dragging);
    });

    item.addEventListener('dragleave',()=>item.classList.remove('is-drag-over'));

    item.querySelectorAll('[data-menu-move]').forEach(button=>{
      button.addEventListener('click',()=>{
        const direction=button.dataset.menuMove;
        if(direction==='up'){
          const previous=item.previousElementSibling;
          if(previous) list.insertBefore(item,previous);
        }else{
          const next=item.nextElementSibling;
          if(next) list.insertBefore(next,item);
        }
        sync();
        item.scrollIntoView({block:'nearest',behavior:'smooth'});
      });
    });
  });

  sync();
})();

(function(){
  const preview=document.querySelector('[data-a11y-admin-preview]');
  if(!preview) return;

  const title=preview.querySelector('[data-a11y-admin-preview-title]');
  const text=preview.querySelector('[data-a11y-admin-preview-text]');

  const textTargets={
    button:preview.querySelector('[data-a11y-admin-preview-button]'),
    title,
    text
  };

  document.querySelectorAll('[data-a11y-admin-text]').forEach(input=>{
    const key=input.dataset.a11yAdminText;
    const target=textTargets[key];
    if(!target) return;
    input.addEventListener('input',()=>{target.textContent=input.value;});
  });

  const vars={
    'panel-bg':'--ap-bg',
    'panel-text':'--ap-text',
    'accent':'--ap-accent',
    'border':'--ap-border',
    'primary-bg':'--ap-primary',
    'primary-text':'--ap-primary-text'
  };

  document.querySelectorAll('[data-a11y-admin-color]').forEach(input=>{
    const code=input.closest('.accessibility-color-control')?.querySelector('code');
    const sync=()=>{
      const cssVar=vars[input.dataset.a11yAdminColor];
      if(cssVar) preview.style.setProperty(cssVar,input.value);
      if(code) code.textContent=input.value;
    };
    input.addEventListener('input',sync);
    sync();
  });
})();
</script>

<?php require __DIR__.'/_bottom.php'; ?>
