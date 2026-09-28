<?php
require dirname(__DIR__) . '/app/bootstrap.php'; require_admin();
$keys=['site_name','site_subtitle','hero_kicker','editor_note','footer_quote'];
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 foreach($keys as $k) save_setting($k,trim($_POST[$k]??''));
 header('Location: '.base_url('admin/settings.php?saved=1'));
 exit;
}
$adminTitle='Настройки сайта';
require __DIR__.'/_top.php';
?>
<?php if(isset($_GET['saved'])):?><div class="ok">Настройки сохранены.</div><?php endif;?>
<form method="post" class="admin-card settings-form"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><label>Название сайта<input name="site_name" value="<?=e(setting('site_name'))?>"></label><label>Подзаголовок<input name="site_subtitle" value="<?=e(setting('site_subtitle'))?>"></label><label>Подпись над главной новостью<input name="hero_kicker" value="<?=e(setting('hero_kicker'))?>"></label><label>Слово редактора<textarea name="editor_note" rows="7"><?=e(setting('editor_note'))?></textarea></label><label>Цитата внизу сайта<input name="footer_quote" value="<?=e(setting('footer_quote'))?>"></label><button class="primary">Сохранить</button></form>
<?php require __DIR__.'/_bottom.php'; ?>
