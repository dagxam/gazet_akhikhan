<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();
$adminTitle='Обзор';
$stats=[
 'published'=>(int)db()->query("SELECT COUNT(*) FROM articles WHERE status='published'")->fetchColumn(),
 'drafts'=>(int)db()->query("SELECT COUNT(*) FROM articles WHERE status='draft'")->fetchColumn(),
 'views'=>(int)db()->query("SELECT COALESCE(SUM(views),0) FROM articles")->fetchColumn(),
 'categories'=>(int)db()->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
];
$recent=db()->query("SELECT id,title,status,updated_at FROM articles ORDER BY updated_at DESC LIMIT 8")->fetchAll();
require __DIR__.'/_top.php';
?>
<div class="stats"><div><b><?=$stats['published']?></b><span>Опубликовано</span></div><div><b><?=$stats['drafts']?></b><span>Черновиков</span></div><div><b><?=number_format($stats['views'],0,'.',' ')?></b><span>Просмотров</span></div><div><b><?=$stats['categories']?></b><span>Рубрик</span></div></div>
<section class="admin-card"><div class="card-head"><h2>Последние материалы</h2><a class="primary" href="<?=e(base_url('admin/article-edit.php'))?>">+ Добавить</a></div><table><thead><tr><th>Название</th><th>Статус</th><th>Изменено</th></tr></thead><tbody><?php foreach($recent as $r):?><tr><td><a href="<?=e(base_url('admin/article-edit.php?id='.$r['id']))?>"><?=e($r['title'])?></a></td><td><span class="status <?=$r['status']==='published'?'green':'gray'?>"><?=e($r['status']==='published'?'Опубликовано':'Черновик')?></span></td><td><?=e(ru_date($r['updated_at']))?></td></tr><?php endforeach;?></tbody></table></section>
<?php require __DIR__.'/_bottom.php'; ?>
