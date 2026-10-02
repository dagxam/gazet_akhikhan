<?php
require __DIR__ . '/app/bootstrap.php';
if (!APP_INSTALLED) { header('Location: install.php'); exit; }

$documentsTotal=(int)db()->query("SELECT COUNT(*) FROM documents WHERE status='published'")->fetchColumn();
$documentsPager=public_pagination_state($documentsTotal,18,'page');
$documentsLimit=(int)$documentsPager['per_page'];
$documentsOffset=(int)$documentsPager['offset'];
$documents=db()->query("SELECT * FROM documents WHERE status='published' ORDER BY document_date DESC,id DESC LIMIT ".$documentsLimit." OFFSET ".$documentsOffset)->fetchAll();

$pageTitle = 'Документы';
$pageDescription = 'Документы и официальные материалы сетевого издания «АХИХЪАН»: PDF, Word, Excel и PowerPoint.';
$seoCanonical = public_pagination_url('documents.php',$documentsPager['page']);

$documentItems=[];
foreach(array_slice($documents,0,50) as $index=>$doc){
    $documentItems[]=[
        '@type'=>'ListItem',
        'position'=>$index+1,
        'name'=>(string)$doc['title'],
        'url'=>base_url((string)$doc['file_path']),
    ];
}
$seoJsonLd=[[
    '@context'=>'https://schema.org',
    '@type'=>'CollectionPage',
    '@id'=>$seoCanonical.'#collection',
    'url'=>$seoCanonical,
    'name'=>'Документы — АХИХЪАН',
    'description'=>$pageDescription,
    'isPartOf'=>['@id'=>base_url('#website')],
    'mainEntity'=>[
        '@type'=>'ItemList',
        'numberOfItems'=>$documentsTotal,
        'itemListElement'=>$documentItems,
    ],
]];

require __DIR__ . '/partials/header.php';
?>

<div class="wrap public-documents-page">
  <header class="public-documents-hero">
    <div>
      <span class="heading-kicker">Официальные материалы</span>
      <h1>Документы</h1>
      <p>Все опубликованные документы редакции: PDF, Word, Excel и PowerPoint. Новые материалы появляются здесь автоматически после публикации в админ-панели.</p>
    </div>
    <span class="public-documents-count"><?=$documentsTotal?> <?=$documentsTotal===1?'документ':'документов'?></span>
  </header>

  <?php if($documents):?>
    <div class="public-documents-grid">
      <?php foreach($documents as $doc):
        $formatClass=document_format_class((string)$doc['file_ext']);
        $formatLabel=document_format_label((string)$doc['file_ext']);
      ?>
        <article class="public-document-card">
          <a class="public-document-icon document-format-icon <?=$formatClass?>" href="<?=e(base_url($doc['file_path']))?>" target="_blank" rel="noopener" aria-label="Открыть <?=e($doc['title'])?>">
            <b><?=e(strtoupper((string)$doc['file_ext']))?></b>
            <small><?=e($formatLabel)?></small>
          </a>
          <div class="public-document-copy">
            <div class="public-document-meta">
              <time datetime="<?=e((string)$doc['document_date'])?>"><?=e(ru_date((string)$doc['document_date']))?></time>
              <span><?=e(human_file_size((int)$doc['file_size']))?></span>
            </div>
            <h2><a href="<?=e(base_url($doc['file_path']))?>" target="_blank" rel="noopener"><?=e($doc['title'])?></a></h2>
            <?php if(!empty($doc['description'])):?>
              <p><?=e(rich_text_excerpt((string)$doc['description'],220))?></p>
            <?php endif;?>
            <a class="public-document-open" href="<?=e(base_url($doc['file_path']))?>" target="_blank" rel="noopener">
              Открыть документ <span>→</span>
            </a>
          </div>
        </article>
      <?php endforeach;?>
    </div>
    <?php render_public_pagination('documents.php',$documentsPager['page'],$documentsPager['total_pages'],[],'page','Страницы документов'); ?>
  <?php else:?>
    <div class="public-documents-empty">
      <span class="document-format-icon file"><b>DOC</b><small>ФАЙЛ</small></span>
      <div>
        <strong>Документы пока не опубликованы</strong>
        <p>После публикации материалов в разделе «Документы» они автоматически появятся на этой странице.</p>
      </div>
    </div>
  <?php endif;?>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
