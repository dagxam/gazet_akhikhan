<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$perPage=15;
$page=max(1,(int)($_GET['page']??1));

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_id'])){
  verify_csrf();
  $deleteId=(int)$_POST['delete_id'];
  $q=db()->prepare('SELECT cover_image FROM articles WHERE id=? LIMIT 1');
  $q->execute([$deleteId]);
  $deleteArticle=$q->fetch();
  if($deleteArticle){
    foreach(article_images($deleteId) as $image){
      safe_delete_article_image($image['image_path']??null);
    }
    safe_delete_article_image($deleteArticle['cover_image']??null);
    db()->prepare('DELETE FROM articles WHERE id=?')->execute([$deleteId]);
  }
  $returnPage=max(1,(int)($_POST['page']??1));
  header('Location: '.base_url('admin/articles.php?page='.$returnPage));
  exit;
}

$total=(int)db()->query("SELECT COUNT(*) FROM articles")->fetchColumn();
$totalPages=max(1,(int)ceil($total/$perPage));
if($page>$totalPages) $page=$totalPages;
$offset=($page-1)*$perPage;

$q=db()->prepare("SELECT * FROM articles ORDER BY updated_at DESC LIMIT ? OFFSET ?");
$q->bindValue(1,$perPage,PDO::PARAM_INT);
$q->bindValue(2,$offset,PDO::PARAM_INT);
$q->execute();
$rows=$q->fetchAll();

foreach($rows as &$row){
  $row['category_names']=implode(', ',array_column(article_categories((int)$row['id']),'name'));
}
unset($row);

$startItem=$total ? $offset+1 : 0;
$endItem=min($offset+$perPage,$total);

$adminTitle='Новости';
require __DIR__.'/_top.php';
?>

<section class="admin-card news-admin-card">
  <div class="card-head news-card-head">
    <div>
      <h2>Все новости</h2>
      <p class="admin-intro">Всего: <b><?=number_format($total,0,'.',' ')?></b> · показано <?=$startItem?>–<?=$endItem?> · по <?=$perPage?> материалов на странице.</p>
    </div>
    <a class="primary" href="<?=e(base_url('admin/article-edit.php'))?>">+ Добавить новость</a>
  </div>

  <div class="table-scroll">
    <table class="news-table">
      <thead>
        <tr><th>Новость</th><th>Рубрики</th><th>Дата</th><th>Статус</th><th>Просмотры</th><th>Действия</th></tr>
      </thead>
      <tbody>
        <?php foreach($rows as $r):
          $materialDate=$r['published_at'] ?: $r['created_at'];
        ?>
          <tr>
            <td>
              <a class="news-title-link" href="<?=e(base_url('admin/article-edit.php?id='.$r['id']))?>"><strong><?=e($r['title'])?></strong></a>
              <small><?=e($r['slug'])?></small>
            </td>
            <td><?=e($r['category_names'] ?: '—')?></td>
            <td class="news-date-cell"><b><?=e(ru_date($materialDate))?></b><small>изменено <?=e(ru_date($r['updated_at']))?></small></td>
            <td><span class="status <?=$r['status']==='published'?'green':'gray'?>"><?=$r['status']==='published'?'Опубликовано':'Черновик'?></span></td>
            <td><?=number_format((int)$r['views'],0,'.',' ')?></td>
            <td>
              <div class="row-actions">
                <a class="edit-action" href="<?=e(base_url('admin/article-edit.php?id='.$r['id']))?>">Редактировать</a>
                <form method="post" onsubmit="return confirm('Удалить новость?')">
                  <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                  <input type="hidden" name="delete_id" value="<?=$r['id']?>">
                  <input type="hidden" name="page" value="<?=$page?>">
                  <button class="danger">Удалить</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach;?>
        <?php if(!$rows):?>
          <tr><td colspan="6"><div class="news-list-empty">Новостей пока нет.</div></td></tr>
        <?php endif;?>
      </tbody>
    </table>
  </div>

  <?php if($totalPages>1):?>
    <nav class="admin-pagination" aria-label="Страницы новостей">
      <?php if($page>1):?><a class="admin-page-arrow" href="<?=e(base_url('admin/articles.php?page='.($page-1)))?>" aria-label="Предыдущая страница">←</a><?php endif;?>

      <?php
      $pages=[];
      if($totalPages<=9){
        $pages=range(1,$totalPages);
      }else{
        $pages=[1];
        $from=max(2,$page-2);
        $to=min($totalPages-1,$page+2);
        if($from>2) $pages[]='…';
        for($i=$from;$i<=$to;$i++) $pages[]=$i;
        if($to<$totalPages-1) $pages[]='…';
        $pages[]=$totalPages;
      }
      foreach($pages as $p):
        if($p==='…'):
      ?>
        <span class="admin-page-gap">…</span>
      <?php else:?>
        <a class="<?=$p===$page?'is-active':''?>" href="<?=e(base_url('admin/articles.php?page='.$p))?>"><?=$p?></a>
      <?php endif; endforeach;?>

      <?php if($page<$totalPages):?><a class="admin-page-arrow" href="<?=e(base_url('admin/articles.php?page='.($page+1)))?>" aria-label="Следующая страница">→</a><?php endif;?>
    </nav>
  <?php endif;?>
</section>

<?php require __DIR__.'/_bottom.php'; ?>
