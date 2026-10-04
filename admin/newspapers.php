<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_editor_permission('newspapers');

$id=(int)($_GET['id']??0);
$editing=null;
$error='';

if($id){
  $q=db()->prepare('SELECT * FROM newspapers WHERE id=?');
  $q->execute([$id]);
  $editing=$q->fetch();
  if(!$editing) $id=0;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
  verify_csrf();

  try{
    if(isset($_POST['delete_id'])){
      $deleteId=(int)$_POST['delete_id'];
      $q=db()->prepare('SELECT * FROM newspapers WHERE id=?');
      $q->execute([$deleteId]);
      $row=$q->fetch();

      if($row){
        safe_delete_newspaper_upload($row['pdf_file']??null);
        safe_delete_newspaper_upload($row['cover_image']??null);
        db()->prepare('DELETE FROM newspapers WHERE id=?')->execute([$deleteId]);
      }

      header('Location: '.base_url('admin/newspapers.php?deleted=1'));
      exit;
    }

    $saveId=(int)($_POST['id']??0);
    $current=null;
    if($saveId){
      $q=db()->prepare('SELECT * FROM newspapers WHERE id=?');
      $q->execute([$saveId]);
      $current=$q->fetch();
      if(!$current) throw new RuntimeException('Выпуск не найден.');
    }

    $title=trim($_POST['title']??'');
    if($title==='') $title='Газета «АХИХЪАН»';

    $issueNumber=trim($_POST['issue_number']??'');
    $issueDate=trim($_POST['issue_date']??'') ?: date('Y-m-d');
    $status=in_array($_POST['status']??'published',['draft','published'],true)?$_POST['status']:'published';

    $files=handle_newspaper_pdf_upload(
      $_FILES['pdf_file']??[],
      $current['pdf_file']??null,
      $current['cover_image']??null
    );

    if(empty($files['pdf_file'])) throw new RuntimeException('Загрузите PDF выпуска.');

    if($saveId){
      $q=db()->prepare('UPDATE newspapers SET title=?,issue_number=?,issue_date=?,pdf_file=?,cover_image=?,status=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
      $q->execute([$title,$issueNumber!==''?$issueNumber:null,$issueDate,$files['pdf_file'],$files['cover_image'],$status,$saveId]);
      $savedId=$saveId;
    }else{
      $q=db()->prepare('INSERT INTO newspapers(title,issue_number,issue_date,pdf_file,cover_image,status) VALUES(?,?,?,?,?,?)');
      $q->execute([$title,$issueNumber!==''?$issueNumber:null,$issueDate,$files['pdf_file'],$files['cover_image'],$status]);
      $savedId=(int)db()->lastInsertId();
    }

    header('Location: '.base_url('admin/newspapers.php?id='.$savedId.'&saved=1'));
    exit;

  }catch(Throwable $e){
    $error=$e->getMessage();
  }
}

if($id){
  $q=db()->prepare('SELECT * FROM newspapers WHERE id=?');
  $q->execute([$id]);
  $editing=$q->fetch();
}

$rows=db()->query('SELECT * FROM newspapers ORDER BY issue_date DESC,id DESC')->fetchAll();
$newspaperPager=admin_paginate_array($rows,8,'page');
$rows=$newspaperPager['items'];

$adminTitle='Газета';
require __DIR__.'/_top.php';
?>
<?php if($error):?><div class="error"><?=e($error)?></div><?php endif;?>
<?php if(isset($_GET['saved'])):?><div class="ok">Выпуск газеты сохранён.</div><?php endif;?>
<?php if(isset($_GET['deleted'])):?><div class="ok">Выпуск удалён.</div><?php endif;?>

<div class="newspaper-admin-head">
  <div>
    <span class="editor-eyebrow">Архив издания</span>
    <h2>Газета «АХИХЪАН»</h2>
    <p>Загружайте выпуски в PDF. Последний опубликованный выпуск автоматически появится на главной странице.</p>
  </div>
  <?php if($editing):?><a class="editor-back" href="<?=e(base_url('admin/newspapers.php'))?>">＋ Новый выпуск</a><?php endif;?>
</div>

<div class="newspaper-admin-layout">
  <section class="editor-card newspaper-upload-card">
    <div class="side-card-title">
      <span class="side-icon">PDF</span>
      <div>
        <h3><?=$editing?'Редактировать выпуск':'Добавить выпуск'?></h3>
        <p>PDF, номер, дата и статус публикации</p>
      </div>
    </div>

    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
      <input type="hidden" name="id" value="<?=e((string)($editing['id']??0))?>">

      <label class="field-modern compact">
        <span>Название</span>
        <input name="title" value="<?=e($editing['title']??'Газета «АХИХЪАН»')?>" placeholder="Газета «АХИХЪАН»">
      </label>

      <div class="newspaper-form-row">
        <label class="field-modern compact">
          <span>Номер выпуска</span>
          <input name="issue_number" value="<?=e($editing['issue_number']??'')?>" placeholder="Например: № 38">
        </label>
        <label class="field-modern compact">
          <span>Дата выпуска</span>
          <input type="date" name="issue_date" value="<?=e($editing['issue_date']??date('Y-m-d'))?>">
        </label>
      </div>

      <label class="field-modern compact">
        <span>Статус</span>
        <select name="status">
          <option value="published" <?=($editing['status']??'published')==='published'?'selected':''?>>Опубликовано</option>
          <option value="draft" <?=($editing['status']??'')==='draft'?'selected':''?>>Черновик</option>
        </select>
      </label>

      <label class="pdf-dropzone">
        <input type="file" name="pdf_file" accept="application/pdf,.pdf" <?=$editing?'':'required'?>>
        <span class="pdf-drop-icon">PDF</span>
        <b><?=$editing?'Заменить PDF':'Выбрать PDF газеты'?></b>
        <small>До 80 МБ · первая страница станет обложкой автоматически</small>
      </label>

      <?php if($editing && !empty($editing['pdf_file'])):?>
        <a class="current-pdf-link" href="<?=e(base_url($editing['pdf_file']))?>" target="_blank">Открыть текущий PDF ↗</a>
      <?php endif;?>

      <button class="primary wide newspaper-save" type="submit"><?=$editing?'Сохранить изменения':'Добавить выпуск'?></button>
    </form>
  </section>

  <section class="editor-card newspaper-library-card">
    <div class="card-head">
      <div>
        <h2>Выпуски</h2>
        <p class="admin-intro">Последний опубликованный выпуск показывается на главной.</p>
      </div>
    </div>

    <?php if($rows):?>
      <div class="newspaper-admin-list">
        <?php foreach($rows as $row):?>
          <article class="newspaper-admin-item">
            <a class="newspaper-admin-cover" href="<?=e(base_url($row['pdf_file']))?>" target="_blank">
              <?php if(!empty($row['cover_image'])):?>
                <img src="<?=e(base_url($row['cover_image']))?>" alt="<?=e($row['title'])?>">
              <?php else:?>
                <canvas data-pdf-preview="<?=e(base_url($row['pdf_file']))?>" aria-label="Первая страница PDF"></canvas>
                <span class="pdf-cover-fallback">PDF</span>
              <?php endif;?>
            </a>

            <div class="newspaper-admin-info">
              <span class="status <?=$row['status']==='published'?'green':'gray'?>"><?=$row['status']==='published'?'Опубликовано':'Черновик'?></span>
              <h3><?=e($row['title'])?></h3>
              <p><?=e($row['issue_number'] ?: 'Без номера')?> · <?=e(ru_date($row['issue_date']))?></p>
              <div class="row-actions">
                <a class="edit-action" href="<?=e(base_url('admin/newspapers.php?id='.$row['id']))?>">Редактировать</a>
                <a class="edit-action" href="<?=e(base_url($row['pdf_file']))?>" target="_blank">PDF ↗</a>
                <form method="post" data-confirm="Удалить этот выпуск газеты?">
                  <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                  <input type="hidden" name="delete_id" value="<?=$row['id']?>">
                  <button class="danger">Удалить</button>
                </form>
              </div>
            </div>
          </article>
        <?php endforeach;?>
      </div>
      <?php render_admin_pagination('admin/newspapers.php',$newspaperPager['page'],$newspaperPager['total_pages'],[],'page','Страницы выпусков'); ?>
    <?php else:?>
      <div class="newspaper-admin-empty">
        <b>Выпусков пока нет</b>
        <span>Загрузите первый PDF — после публикации он появится на главной странице.</span>
      </div>
    <?php endif;?>
  </section>
</div>

<script nonce="<?=e(csp_nonce())?>" type="module">
const canvases=[...document.querySelectorAll('canvas[data-pdf-preview]')];
if(canvases.length){
  try{
    const pdfjs=await import('https://cdn.jsdelivr.net/npm/pdfjs-dist@4.10.38/build/pdf.min.mjs');
    pdfjs.GlobalWorkerOptions.workerSrc='https://cdn.jsdelivr.net/npm/pdfjs-dist@4.10.38/build/pdf.worker.min.mjs';

    for(const canvas of canvases){
      try{
        const pdf=await pdfjs.getDocument(canvas.dataset.pdfPreview).promise;
        const page=await pdf.getPage(1);
        const base=page.getViewport({scale:1});
        const cssWidth=Math.max(160,canvas.parentElement.clientWidth);
        const ratio=Math.min(window.devicePixelRatio||1,2);
        const viewport=page.getViewport({scale:(cssWidth*ratio)/base.width});
        canvas.width=Math.floor(viewport.width);
        canvas.height=Math.floor(viewport.height);
        await page.render({canvasContext:canvas.getContext('2d'),viewport}).promise;
        canvas.classList.add('is-rendered');
        canvas.parentElement.querySelector('.pdf-cover-fallback')?.remove();
      }catch(e){}
    }
  }catch(e){}
}
</script>

<?php require __DIR__.'/_bottom.php'; ?>
