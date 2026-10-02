<?php
require __DIR__ . '/app/bootstrap.php';
if (!APP_INSTALLED) { header('Location: install.php'); exit; }

$pageTitle='Контакты редакции';
$pageDescription='Контакты редакции сетевого издания «АХИХЪАН» и форма обратной связи.';
$seoCanonical=base_url('contacts.php');

$operatorName=trim(setting('pd_operator_name',''));
$operatorAddress=trim(setting('pd_operator_address',''));
$operatorEmail=trim(setting('pd_operator_email',''));
$operatorPhone=trim(setting('pd_operator_phone',''));
$editorialEmail='info@akhikhan.ru';

$topics=[
  'news'=>'Предложить новость',
  'editorial'=>'Вопрос редакции',
  'advertising'=>'Реклама и сотрудничество',
  'correction'=>'Исправление или уточнение публикации',
  'personal'=>'Персональные данные',
  'other'=>'Другое обращение',
];

$form=[
  'name'=>'',
  'email'=>'',
  'phone'=>'',
  'topic'=>'editorial',
  'message'=>'',
];
$formError='';

if($_SERVER['REQUEST_METHOD']==='POST'){
  verify_csrf();

  $form['name']=trim((string)($_POST['name']??''));
  $form['email']=trim((string)($_POST['email']??''));
  $form['phone']=trim((string)($_POST['phone']??''));
  $form['topic']=trim((string)($_POST['topic']??'editorial'));
  $form['message']=trim((string)($_POST['message']??''));
  $honeypot=trim((string)($_POST['website']??''));

  try{
    if($honeypot!==''){
      header('Location: '.base_url('contacts.php?sent=1'));
      exit;
    }

    $rate=contact_form_rate_limit(false);
    if(!empty($rate['blocked'])){
      $minutes=max(1,(int)ceil(((int)$rate['remaining'])/60));
      throw new RuntimeException('Слишком много отправок. Повторите через '.$minutes.' мин.');
    }

    if(function_exists('mb_strlen')){
      $nameLen=mb_strlen($form['name'],'UTF-8');
      $messageLen=mb_strlen($form['message'],'UTF-8');
    }else{
      $nameLen=strlen($form['name']);
      $messageLen=strlen($form['message']);
    }

    if($nameLen<2 || $nameLen>120) throw new RuntimeException('Укажите имя от 2 до 120 символов.');
    if(!filter_var($form['email'],FILTER_VALIDATE_EMAIL) || strlen($form['email'])>190) throw new RuntimeException('Укажите корректный e-mail.');
    if(preg_match('/[\r\n]/',$form['email'])) throw new RuntimeException('Некорректный e-mail.');
    if($form['phone']!=='' && (strlen($form['phone'])>50 || !preg_match('/^[0-9+()\- .]{5,50}$/u',$form['phone']))) throw new RuntimeException('Проверьте номер телефона.');
    if(!isset($topics[$form['topic']])) throw new RuntimeException('Выберите тему обращения.');
    if($messageLen<20 || $messageLen>5000) throw new RuntimeException('Сообщение должно содержать от 20 до 5000 символов.');
    if(empty($_POST['privacy_consent'])) throw new RuntimeException('Подтвердите согласие на обработку данных для ответа на обращение.');

    contact_form_rate_limit(true);

    $reference='AKH-'.date('Ymd').'-'.strtoupper(substr(bin2hex(random_bytes(4)),0,6));
    $sent=send_contact_email([
      'name'=>$form['name'],
      'email'=>$form['email'],
      'phone'=>$form['phone'],
      'topic'=>$topics[$form['topic']],
      'message'=>$form['message'],
      'reference'=>$reference,
      'sent_at'=>date('d.m.Y H:i'),
    ]);

    if(!$sent){
      throw new RuntimeException('Не удалось передать письмо почтовому серверу. Попробуйте позже или напишите напрямую на info@akhikhan.ru.');
    }

    header('Location: '.base_url('contacts.php?sent=1&ref='.rawurlencode($reference)));
    exit;
  }catch(Throwable $e){
    $formError=$e->getMessage();
  }
}

$sentOk=isset($_GET['sent']);
$sentRef=trim((string)($_GET['ref']??''));

require __DIR__.'/partials/header.php';
?>
<div class="wrap contacts-page">
  <section class="contacts-hero">
    <div class="contacts-hero-copy">
      <span class="heading-kicker">Редакция «АХИХЪАН»</span>
      <h1>Связаться с редакцией</h1>
      <p>Новости, уточнения, предложения, реклама и сотрудничество — отправьте сообщение прямо с сайта.</p>
    </div>
    <div class="contacts-hero-mark" aria-hidden="true">
      <span><i class="fa-regular fa-envelope"></i></span>
      <b>info@akhikhan.ru</b>
      <small>официальная почта редакции</small>
    </div>
  </section>

  <?php if($sentOk):?>
    <section class="contact-success" role="status">
      <span class="contact-success-icon"><i class="fa-solid fa-check"></i></span>
      <div>
        <span class="heading-kicker">Сообщение отправлено</span>
        <h2>Спасибо за обращение</h2>
        <p>Письмо передано в редакцию на <strong>info@akhikhan.ru</strong>. Ответ придёт на e-mail, который вы указали в форме.</p>
        <?php if($sentRef!==''):?><small>Номер обращения: <b><?=e($sentRef)?></b></small><?php endif;?>
      </div>
    </section>
  <?php endif;?>

  <div class="contacts-layout">
    <section class="contact-form-card">
      <div class="contact-card-head">
        <span class="contact-card-icon"><i class="fa-regular fa-paper-plane"></i></span>
        <div>
          <span class="heading-kicker">Обратная связь</span>
          <h2>Написать в редакцию</h2>
          <p>Заполните форму — письмо будет отправлено с сайта напрямую в редакционную почту.</p>
        </div>
      </div>

      <?php if($formError!==''):?>
        <div class="contact-form-error"><i class="fa-solid fa-circle-exclamation"></i><span><?=e($formError)?></span></div>
      <?php endif;?>

      <form class="contact-form" method="post" novalidate>
        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">

        <label class="contact-honeypot" aria-hidden="true">
          <span>Ваш сайт</span>
          <input name="website" tabindex="-1" autocomplete="off">
        </label>

        <div class="contact-form-row">
          <label class="contact-field">
            <span>Ваше имя <b>*</b></span>
            <input name="name" maxlength="120" required autocomplete="name" value="<?=e($form['name'])?>" placeholder="Как к вам обращаться">
          </label>
          <label class="contact-field">
            <span>E-mail <b>*</b></span>
            <input type="email" name="email" maxlength="190" required autocomplete="email" value="<?=e($form['email'])?>" placeholder="name@example.ru">
          </label>
        </div>

        <div class="contact-form-row">
          <label class="contact-field">
            <span>Телефон</span>
            <input name="phone" maxlength="50" autocomplete="tel" value="<?=e($form['phone'])?>" placeholder="+7 ...">
          </label>
          <label class="contact-field">
            <span>Тема обращения <b>*</b></span>
            <select name="topic" required>
              <?php foreach($topics as $value=>$label):?>
                <option value="<?=e($value)?>" <?=$form['topic']===$value?'selected':''?>><?=e($label)?></option>
              <?php endforeach;?>
            </select>
          </label>
        </div>

        <label class="contact-field contact-message-field">
          <span>Сообщение <b>*</b></span>
          <textarea name="message" rows="8" minlength="20" maxlength="5000" required placeholder="Напишите ваше обращение..."><?=e($form['message'])?></textarea>
          <small>До 5000 символов. Для отправки файлов можно написать напрямую на info@akhikhan.ru.</small>
        </label>

        <label class="contact-consent">
          <input type="checkbox" name="privacy_consent" value="1" required>
          <span>Я согласен(на) на обработку указанных в форме данных для рассмотрения обращения и ответа. <a href="<?=e(base_url('privacy.php'))?>" target="_blank" rel="noopener">Политика обработки персональных данных</a> · <a href="<?=e(base_url('personal-data-consent.php'))?>" target="_blank" rel="noopener">Согласие</a>.</span>
        </label>

        <div class="contact-form-footer">
          <span class="contact-delivery-note"><i class="fa-solid fa-shield-halved"></i> Отправитель письма: АХИХЪАН &lt;info@akhikhan.ru&gt;</span>
          <button class="contact-submit" type="submit"><span>Отправить в редакцию</span><i class="fa-solid fa-arrow-right"></i></button>
        </div>
      </form>
    </section>

    <aside class="contacts-sidebar">
      <section class="contact-info-card contact-info-primary">
        <span class="heading-kicker">Контакты</span>
        <h2>Редакция «АХИХЪАН»</h2>
        <a class="contact-info-line" href="mailto:<?=$editorialEmail?>">
          <span><i class="fa-regular fa-envelope"></i></span>
          <b>Электронная почта</b>
          <small><?=$editorialEmail?></small>
        </a>
        <?php if($operatorPhone!==''):?>
          <a class="contact-info-line" href="tel:<?=e(preg_replace('/[^0-9+]/','',$operatorPhone)??$operatorPhone)?>">
            <span><i class="fa-solid fa-phone"></i></span>
            <b>Телефон</b>
            <small><?=e($operatorPhone)?></small>
          </a>
        <?php endif;?>
        <?php if($operatorAddress!==''):?>
          <div class="contact-info-line">
            <span><i class="fa-solid fa-location-dot"></i></span>
            <b>Адрес</b>
            <small><?=e($operatorAddress)?></small>
          </div>
        <?php endif;?>
      </section>

      <section class="contact-info-card">
        <span class="heading-kicker">Можно обратиться</span>
        <div class="contact-topic-list">
          <div><i class="fa-regular fa-newspaper"></i><span><b>Предложить новость</b><small>События, инициативы и важные темы района.</small></span></div>
          <div><i class="fa-solid fa-pen"></i><span><b>Уточнить публикацию</b><small>Исправления, дополнения и вопросы по материалам.</small></span></div>
          <div><i class="fa-solid fa-handshake"></i><span><b>Сотрудничество</b><small>Реклама, партнёрские и деловые предложения.</small></span></div>
          <div><i class="fa-solid fa-shield-halved"></i><span><b>Персональные данные</b><small>Доступ, уточнение, удаление и отзыв согласия.</small></span></div>
        </div>
      </section>

      <?php if($operatorEmail!=='' && strtolower($operatorEmail)!==strtolower($editorialEmail)):?>
        <section class="contact-info-card contact-privacy-card">
          <span class="heading-kicker">Персональные данные</span>
          <p>Специальный адрес оператора для вопросов об обработке персональных данных:</p>
          <a href="mailto:<?=e($operatorEmail)?>"><?=e($operatorEmail)?> →</a>
        </section>
      <?php endif;?>
    </aside>
  </div>
</div>
<?php require __DIR__.'/partials/footer.php'; ?>
