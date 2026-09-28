<?php
require dirname(__DIR__) . '/app/bootstrap.php'; require_admin();
if($_SERVER['REQUEST_METHOD']==='POST'){
  verify_csrf();
  if(isset($_POST['delete_id'])){
    db()->prepare('DELETE FROM categories WHERE id=?')->execute([(int)$_POST['delete_id']]);
  } else {
    $name=trim($_POST['name']??'');
    if($name){
      $slug=trim($_POST['slug']??'')?:slugify($name);
      $q=db()->prepare('INSERT INTO categories(name,slug,sort_order,is_active) VALUES(?,?,?,1)');
      $q->execute([$name,$slug,(int)($_POST['sort_order']??100)]);
    }
  }
  header('Location: '.base_url('admin/categories.php'));
  exit;
}
$rows=db()->query('SELECT c.*,(SELECT COUNT(*) FROM articles a WHERE a.category_id=c.id) article_count FROM categories c ORDER BY sort_order,name')->fetchAll();
$adminTitle='Рубрики';
require __DIR__.'/_top.php';
?>
<div class="two-col"><section class="admin-card"><h2>Добавить рубрику</h2><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><label>Название<input name="name" required></label><label>URL-адрес<input name="slug" placeholder="автоматически"></label><label>Порядок<input type="number" name="sort_order" value="100"></label><button class="primary">Добавить</button></form></section>
<section class="admin-card"><h2>Существующие рубрики</h2><table><tbody><?php foreach($rows as $r):?><tr><td><strong><?=e($r['name'])?></strong><small>/<?=e($r['slug'])?> · материалов: <?=$r['article_count']?></small></td><td><?php if((int)$r['article_count']===0):?><form method="post" onsubmit="return confirm('Удалить рубрику?')"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="delete_id" value="<?=$r['id']?>"><button class="danger">Удалить</button></form><?php endif;?></td></tr><?php endforeach;?></tbody></table></section></div>
<?php require __DIR__.'/_bottom.php'; ?>
