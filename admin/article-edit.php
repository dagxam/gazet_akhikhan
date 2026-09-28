<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$id=(int)($_GET['id']??0);
$article=null;

if($id){
  $q=db()->prepare('SELECT * FROM articles WHERE id=?');
  $q->execute([$id]);
  $article=$q->fetch();
  if(!$article) exit('Материал не найден');
}

$error='';

if($_SERVER['REQUEST_METHOD']==='POST'){
  verify_csrf();
  try{
    $title=trim($_POST['title']??'');
    if(!$title) throw new RuntimeException('Введите заголовок.');

    $slug=trim($_POST['slug']??'') ?: slugify($title);
    $category=(int)($_POST['category_id']??0) ?: null;
    $excerpt=trim($_POST['excerpt']??'');
    $content=trim($_POST['content']??'');
    $status=in_array($_POST['status']??'draft',['draft','published'],true)?$_POST['status']:'draft';
    $featured=isset($_POST['is_featured'])?1:0;
    $publishedAt=trim($_POST['published_at']??'');
    $publishedAt=$publishedAt?str_replace('T',' ',$publishedAt).(strlen($publishedAt)===16?':00':''):null;
    $cover=handle_cover_upload($_FILES['cover']??[], $article['cover_image']??null);

    if($featured){
      db()->exec('UPDATE articles SET is_featured=0');
    }

    if($id){
      $q=db()->prepare('UPDATE articles SET category_id=?,title=?,slug=?,excerpt=?,content=?,cover_image=?,status=?,is_featured=?,published_at=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
      $q->execute([$category,$title,$slug,$excerpt,$content,$cover,$status,$featured,$publishedAt,$id]);
    } else {
      $q=db()->prepare('INSERT INTO articles(category_id,author_id,title,slug,excerpt,content,cover_image,status,is_featured,published_at) VALUES(?,?,?,?,?,?,?,?,?,?)');
      $q->execute([$category,admin_user()['id'],$title,$slug,$excerpt,$content,$cover,$status,$featured,$publishedAt]);
      $id=(int)db()->lastInsertId();
    }

    header('Location: '.base_url('admin/article-edit.php?id='.$id.'&saved=1'));
    exit;
  }catch(Throwable $e){
    $error=$e->getMessage();
  }
}

if($id){
  $q=db()->prepare('SELECT * FROM articles WHERE id=?');
  $q->execute([$id]);
  $article=$q->fetch();
}

$cats=categories();
$adminTitle=$id?'Редактирование материала':'Новый материал';
require __DIR__.'/_top.php';
?>
<?php if($error):?><div class="error"><?=e($error)?></div><?php endif;?><?php if(isset($_GET['saved'])):?><div class="ok">Изменения сохранены.</div><?php endif;?>
<form class="editor-form" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
<div class="editor-grid"><section class="admin-card"><label>Заголовок<input name="title" value="<?=e($article['title']??'')?>" required></label><label>URL-адрес<input name="slug" value="<?=e($article['slug']??'')?>" placeholder="создастся автоматически"></label><label>Краткое описание<textarea name="excerpt" rows="4"><?=e($article['excerpt']??'')?></textarea></label><label>Текст статьи<textarea class="content-editor" name="content" rows="18"><?=e($article['content']??'')?></textarea></label></section>
<aside><section class="admin-card"><label>Рубрика<select name="category_id"><option value="">Без рубрики</option><?php foreach($cats as $c):?><option value="<?=$c['id']?>" <?=($article['category_id']??null)==$c['id']?'selected':''?>><?=e($c['name'])?></option><?php endforeach;?></select></label><label>Статус<select name="status"><option value="draft" <?=($article['status']??'draft')==='draft'?'selected':''?>>Черновик</option><option value="published" <?=($article['status']??'')==='published'?'selected':''?>>Опубликовано</option></select></label><label>Дата публикации<input type="datetime-local" name="published_at" value="<?=e(!empty($article['published_at'])?date('Y-m-d\TH:i',strtotime($article['published_at'])):'')?>"></label><label class="check"><input type="checkbox" name="is_featured" <?=!empty($article['is_featured'])?'checked':''?>> Главная новость</label><label>Обложка<input type="file" name="cover" accept="image/jpeg,image/png,image/webp"></label><?php if(!empty($article['cover_image'])):?><img class="cover-preview" src="<?=e(base_url($article['cover_image']))?>" alt=""><?php endif;?><button class="primary wide">Сохранить</button></section></aside></div></form>
<?php require __DIR__.'/_bottom.php'; ?>
