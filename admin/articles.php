<?php
require dirname(__DIR__) . '/app/bootstrap.php'; require_admin();
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_id'])){
  verify_csrf();
  db()->prepare('DELETE FROM articles WHERE id=?')->execute([(int)$_POST['delete_id']]);
  header('Location: '.base_url('admin/articles.php'));
  exit;
}
$adminTitle='Новости';
$rows=db()->query("SELECT * FROM articles ORDER BY updated_at DESC")->fetchAll();
foreach($rows as &$row){
  $row['category_names']=implode(', ',array_column(article_categories((int)$row['id']),'name'));
}
unset($row);
require __DIR__.'/_top.php';
?>
<section class="admin-card news-admin-card">
<div class="card-head news-card-head">
  <div><h2>Все новости</h2><p class="admin-intro">Открывайте любую новость для полного редактирования заголовка, текста, рубрик, обложки и публикации.</p></div>
  <a class="primary" href="<?=e(base_url('admin/article-edit.php'))?>">+ Добавить новость</a>
</div>
<div class="table-scroll"><table class="news-table"><thead><tr><th>Новость</th><th>Рубрики</th><th>Статус</th><th>Просмотры</th><th>Действия</th></tr></thead><tbody>
<?php foreach($rows as $r):?><tr>
<td><a class="news-title-link" href="<?=e(base_url('admin/article-edit.php?id='.$r['id']))?>"><strong><?=e($r['title'])?></strong></a><small><?=e($r['slug'])?></small></td>
<td><?=e($r['category_names'] ?: '—')?></td>
<td><span class="status <?=$r['status']==='published'?'green':'gray'?>"><?=e($r['status']==='published'?'Опубликовано':'Черновик')?></span></td>
<td><?=$r['views']?></td>
<td>
  <div class="row-actions">
    <a class="edit-action" href="<?=e(base_url('admin/article-edit.php?id='.$r['id']))?>">Редактировать</a>
    <form method="post" onsubmit="return confirm('Удалить новость?')">
      <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
      <input type="hidden" name="delete_id" value="<?=$r['id']?>">
      <button class="danger">Удалить</button>
    </form>
  </div>
</td>
</tr><?php endforeach;?>
</tbody></table></div></section>
<?php require __DIR__.'/_bottom.php'; ?>
