<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  verify_csrf();
  try{
    if(isset($_POST['delete_id'])){
      $id=(int)$_POST['delete_id'];
      $countQ=db()->prepare('SELECT COUNT(*) FROM article_categories WHERE category_id=?');
      $countQ->execute([$id]);
      if((int)$countQ->fetchColumn()>0) throw new RuntimeException('Нельзя удалить рубрику, пока в ней есть материалы.');
      db()->prepare('DELETE FROM categories WHERE id=?')->execute([$id]);
    } else {
      $name=trim($_POST['name']??'');
      if(!$name) throw new RuntimeException('Введите название рубрики.');
      $slug=trim($_POST['slug']??'') ?: slugify($name);
      $description=sanitize_rich_text($_POST['description']??'');
      $sort=(int)($_POST['sort_order']??100);

      $check=db()->prepare('SELECT id FROM categories WHERE name=? OR slug=? LIMIT 1');
      $check->execute([$name,$slug]);
      if($check->fetchColumn()!==false) throw new RuntimeException('Рубрика с таким названием или URL уже существует.');

      $q=db()->prepare('INSERT INTO categories(name,slug,description,sort_order,is_active) VALUES(?,?,?,?,1)');
      $q->execute([$name,$slug,$description!==''?$description:null,$sort]);
    }
    header('Location: '.base_url('admin/categories.php?saved=1'));
    exit;
  }catch(Throwable $e){
    $error=$e->getMessage();
  }
}
$rows=db()->query('SELECT c.*,(SELECT COUNT(*) FROM article_categories ac WHERE ac.category_id=c.id) article_count FROM categories c ORDER BY sort_order,name')->fetchAll();
$adminTitle='Рубрики';
require __DIR__.'/_top.php';
?>
<?php if($error):?><div class="error"><?=e($error)?></div><?php endif;?>
<?php if(isset($_GET['saved'])):?><div class="ok">Рубрики обновлены.</div><?php endif;?>
<div class="two-col">
<section class="admin-card" id="add-category">
  <h2>Добавить рубрику</h2>
  <p class="admin-intro">Созданная рубрика сразу появится в списке при добавлении или редактировании новости.</p>
  <form method="post">
    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
    <label>Название<input name="name" required placeholder="Например: Образование"></label>
    <label>URL-адрес<input name="slug" placeholder="создастся автоматически"></label>
    <label>Описание<textarea name="description" rows="4" data-rich-text placeholder="Кратко о содержании рубрики"></textarea></label>
    <label>Порядок<input type="number" name="sort_order" value="100"></label>
    <button class="primary">Добавить рубрику</button>
  </form>
</section>
<section class="admin-card">
  <div class="card-head"><div><h2>Существующие рубрики</h2><p class="admin-intro">Базовые рубрики добавляются автоматически и не дублируются.</p></div></div>
  <div class="table-scroll"><table><thead><tr><th>Рубрика</th><th>Материалов</th><th></th></tr></thead><tbody>
  <?php foreach($rows as $r):?><tr>
    <td><strong><?=e($r['name'])?></strong><small>/<?=e($r['slug'])?><?php if(!empty($r['description'])):?> · <?=e(rich_text_plain($r['description']))?><?php endif;?></small></td>
    <td><?=$r['article_count']?></td>
    <td><?php if((int)$r['article_count']===0):?><form method="post" onsubmit="return confirm('Удалить рубрику?')"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="delete_id" value="<?=$r['id']?>"><button class="danger">Удалить</button></form><?php endif;?></td>
  </tr><?php endforeach;?>
  </tbody></table></div>
</section>
</div>
<?php require __DIR__.'/_bottom.php'; ?>
