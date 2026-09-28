<?php
require dirname(__DIR__) . '/app/bootstrap.php'; require_admin();
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_id'])){
  verify_csrf();
  db()->prepare('DELETE FROM articles WHERE id=?')->execute([(int)$_POST['delete_id']]);
  header('Location: '.base_url('admin/articles.php'));
  exit;
}
$adminTitle='Новости';
$rows=db()->query("SELECT a.*,c.name category_name FROM articles a LEFT JOIN categories c ON c.id=a.category_id ORDER BY a.updated_at DESC")->fetchAll();
require __DIR__.'/_top.php';
?>
<section class="admin-card"><div class="card-head"><h2>Все новости</h2><a class="primary" href="<?=e(base_url('admin/article-edit.php'))?>">+ Добавить новость</a></div><div class="table-scroll"><table><thead><tr><th>Новость</th><th>Рубрика</th><th>Статус</th><th>Просмотры</th><th></th></tr></thead><tbody>
<?php foreach($rows as $r):?><tr><td><a href="<?=e(base_url('admin/article-edit.php?id='.$r['id']))?>"><strong><?=e($r['title'])?></strong></a><small><?=e($r['slug'])?></small></td><td><?=e($r['category_name']??'Главные новости')?></td><td><span class="status <?=$r['status']==='published'?'green':'gray'?>"><?=e($r['status']==='published'?'Опубликовано':'Черновик')?></span></td><td><?=$r['views']?></td><td><form method="post" onsubmit="return confirm('Удалить новость?')"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="delete_id" value="<?=$r['id']?>"><button class="danger">Удалить</button></form></td></tr><?php endforeach;?>
</tbody></table></div></section>
<?php require __DIR__.'/_bottom.php'; ?>
