<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$id=(int)($_GET['id']??0);
$editing=null;
$error='';

if($id){
  $q=db()->prepare('SELECT * FROM documents WHERE id=?');
  $q->execute([$id]);
  $editing=$q->fetch();
  if(!$editing) $id=0;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
  verify_csrf();
  try{
    if(isset($_POST['delete_id'])){
      $deleteId=(int)$_POST['delete_id'];
      $q=db()->prepare('SELECT * FROM documents WHERE id=?');
      $q->execute([$deleteId]);
      $row=$q->fetch();
      if($row){
        safe_delete_document_upload($row['file_path']??null);
        db()->prepare('DELETE FROM documents WHERE id=?')->execute([$deleteId]);
      }
      header('Location: '.base_url('admin/documents.php?deleted=1'));
      exit;
    }

    $saveId=(int)($_POST['id']??0);
    $current=null;
    if($saveId){
      $q=db()->prepare('SELECT * FROM documents WHERE id=?');
      $q->execute([$saveId]);
      $current=$q->fetch();
      if(!$current) throw new RuntimeException('Документ не найден.');
    }

    $title=trim($_POST['title']??'');
    if($title==='') throw new RuntimeException('Введите название документа.');

    $description=sanitize_rich_text($_POST['description']??'');
    $documentDate=trim($_POST['document_date']??'') ?: date('Y-m-d');
    $status=in_array($_POST['status']??'published',['draft','published'],true)?$_POST['status']:'published';

    $file=handle_document_upload(
      $_FILES['document_file']??[],
      $current['file_path']??null,
      $current['file_ext']??null,
      $current['original_name']??null,
      (int)($current['file_size']??0)
    );
    if(empty($file['file_path'])) throw new RuntimeException('Загрузите файл документа.');

    if($saveId){
      $q=db()->prepare('UPDATE documents SET title=?,description=?,document_date=?,file_path=?,file_ext=?,original_name=?,file_size=?,status=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
      $q->execute([$title,$description!==''?$description:null,$documentDate,$file['file_path'],$file['file_ext'],$file['original_name'],$file['file_size'],$status,$saveId]);
      $savedId=$saveId;
    }else{
      $q=db()->prepare('INSERT INTO documents(title,description,document_date,file_path,file_ext,original_name,file_size,status) VALUES(?,?,?,?,?,?,?,?)');
      $q->execute([$title,$description!==''?$description:null,$documentDate,$file['file_path'],$file['file_ext'],$file['original_name'],$file['file_size'],$status]);
      $savedId=(int)db()->lastInsertId();
    }

    header('Location: '.base_url('admin/documents.php?id='.$savedId.'&saved=1'));
    exit;
  }catch(Throwable $e){
    $error=$e->getMessage();
  }
}

if($id){
  $q=db()->prepare('SELECT * FROM documents WHERE id=?');
  $q->execute([$id]);
  $editing=$q->fetch();
}

$rows=db()->query('SELECT * FROM documents ORDER BY document_date DESC,id DESC')->fetchAll();
$documentsPager=admin_paginate_array($rows,10,'page');
$rows=$documentsPager['items'];
$adminTitle='Документы';
require __DIR__.'/_top.php';
?>
<?php if($error):?><div class="error"><?=e($error)?></div><?php endif;?>
<?php if(isset($_GET['saved'])):?><div class="ok">Документ сохранён.</div><?php endif;?>
<?php if(isset($_GET['deleted'])):?><div class="ok">Документ удалён.</div><?php endif;?>

<div class="documents-admin-head">
  <div>
    <span class="editor-eyebrow">Файлы редакции</span>
    <h2>Документы</h2>
    <p>Добавляйте официальные документы и материалы в PDF, Word, Excel и PowerPoint.</p>
  </div>
  <?php if($editing):?><a class="editor-back" href="<?=e(base_url('admin/documents.php'))?>">＋ Новый документ</a><?php endif;?>
</div>

<div class="documents-admin-layout">
  <section class="editor-card document-upload-card">
    <div class="side-card-title">
      <span class="side-icon">DOC</span>
      <div>
        <h3><?=$editing?'Редактировать документ':'Добавить документ'?></h3>
        <p>Название, дата и файл</p>
      </div>
    </div>

    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
      <input type="hidden" name="id" value="<?=e((string)($editing['id']??0))?>">

      <label class="field-modern compact">
        <span>Название документа</span>
        <input name="title" required value="<?=e($editing['title']??'')?>" placeholder="Например: Постановление администрации">
      </label>

      <label class="field-modern compact">
        <span>Краткое описание</span>
        <textarea name="description" rows="3" data-rich-text placeholder="Необязательно"><?=e($editing['description']??'')?></textarea>
      </label>

      <div class="document-form-row">
        <label class="field-modern compact">
          <span>Дата документа</span>
          <input type="date" name="document_date" value="<?=e($editing['document_date']??date('Y-m-d'))?>">
        </label>
        <label class="field-modern compact">
          <span>Статус</span>
          <select name="status">
            <option value="published" <?=($editing['status']??'published')==='published'?'selected':''?>>Опубликован</option>
            <option value="draft" <?=($editing['status']??'')==='draft'?'selected':''?>>Черновик</option>
          </select>
        </label>
      </div>

      <label class="document-dropzone">
        <input type="file" name="document_file" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx" <?=$editing?'':'required'?>>
        <span class="document-drop-icon">FILE</span>
        <b><?=$editing?'Заменить файл':'Выбрать документ'?></b>
        <small>PDF · DOC/DOCX · XLS/XLSX · PPT/PPTX · до 80 МБ</small>
      </label>

      <?php if($editing && !empty($editing['file_path'])):?>
        <div class="document-current-file">
          <b><?=e(strtoupper($editing['file_ext']))?></b>
          <span><?=e($editing['original_name'] ?: basename($editing['file_path']))?> · <?=e(human_file_size((int)$editing['file_size']))?></span>
        </div>
      <?php endif;?>

      <button class="primary wide newspaper-save" type="submit"><?=$editing?'Сохранить изменения':'Добавить документ'?></button>
    </form>
  </section>

  <section class="editor-card document-library-card">
    <div class="card-head">
      <div>
        <h2>Все документы</h2>
        <p class="admin-intro">Опубликованные документы автоматически попадают в горизонтальный блок на главной.</p>
      </div>
    </div>

    <?php if($rows):?>
      <div class="document-admin-list">
        <?php foreach($rows as $row):
          $class=document_format_class($row['file_ext']);
          $label=document_format_label($row['file_ext']);
        ?>
          <article class="document-admin-item">
            <a class="document-admin-icon <?=$class?>" href="<?=e(base_url($row['file_path']))?>" target="_blank">
              <b><?=e(strtoupper($row['file_ext']))?></b>
              <small><?=e($label)?></small>
            </a>
            <div class="document-admin-info">
              <span class="status <?=$row['status']==='published'?'green':'gray'?>"><?=$row['status']==='published'?'Опубликован':'Черновик'?></span>
              <h3><?=e($row['title'])?></h3>
              <?php if(!empty($row['description'])):?><div class="rich-text document-admin-description"><?=rich_text_html($row['description'])?></div><?php endif;?>
              <div class="document-admin-meta">
                <span><?=e(ru_date($row['document_date']))?></span>
                <span><?=e(strtoupper($row['file_ext']))?></span>
                <span><?=e(human_file_size((int)$row['file_size']))?></span>
              </div>
              <div class="row-actions">
                <a class="edit-action" href="<?=e(base_url('admin/documents.php?id='.$row['id']))?>">Редактировать</a>
                <a class="edit-action" href="<?=e(base_url($row['file_path']))?>" target="_blank">Открыть ↗</a>
                <form method="post" data-confirm="Удалить этот документ?">
                  <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                  <input type="hidden" name="delete_id" value="<?=$row['id']?>">
                  <button class="danger">Удалить</button>
                </form>
              </div>
            </div>
          </article>
        <?php endforeach;?>
      </div>
      <?php render_admin_pagination('admin/documents.php',$documentsPager['page'],$documentsPager['total_pages'],[],'page','Страницы документов'); ?>
    <?php else:?>
      <div class="document-admin-empty">
        <b>Документов пока нет</b>
        <span>Добавьте первый файл — опубликованные документы появятся на главной странице.</span>
      </div>
    <?php endif;?>
  </section>
</div>

<?php require __DIR__.'/_bottom.php'; ?>
