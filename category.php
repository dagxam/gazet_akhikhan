<?php
require __DIR__ . '/app/bootstrap.php';
if (!APP_INSTALLED) { header('Location: install.php'); exit; }

$slug = trim($_GET['slug'] ?? '');
$q = db()->prepare('SELECT * FROM categories WHERE slug=? AND is_active=1 LIMIT 1');
$q->execute([$slug]);
$category=$q->fetch();

if(!$category){
  http_response_code(404);
  $pageTitle='Рубрика не найдена';
  $pageDescription='Запрошенная рубрика не найдена.';
  $seoRobots='noindex,nofollow,noarchive';
  require __DIR__.'/partials/header.php';
  echo '<div class="wrap page-shell"><div class="page-head"><div><span class="heading-kicker">404</span><h1>Рубрика не найдена</h1></div><div class="page-head-mark"></div></div></div>';
  require __DIR__.'/partials/footer.php';
  exit;
}

$countQ=db()->prepare("SELECT COUNT(*) FROM articles a
WHERE a.status='published'
AND (a.published_at IS NULL OR a.published_at<=CURRENT_TIMESTAMP)
AND (
  a.category_id=?
  OR EXISTS (
    SELECT 1 FROM article_categories ac
    WHERE ac.article_id=a.id AND ac.category_id=?
  )
)");
$countQ->execute([$category['id'],$category['id']]);
$categoryTotal=(int)$countQ->fetchColumn();
$categoryPager=public_pagination_state($categoryTotal,18,'page');
$categoryLimit=(int)$categoryPager['per_page'];
$categoryOffset=(int)$categoryPager['offset'];

$q=db()->prepare("SELECT a.*,? AS category_name,? AS category_slug
FROM articles a
WHERE a.status='published'
AND (a.published_at IS NULL OR a.published_at<=CURRENT_TIMESTAMP)
AND (
  a.category_id=?
  OR EXISTS (
    SELECT 1 FROM article_categories ac
    WHERE ac.article_id=a.id AND ac.category_id=?
  )
)
ORDER BY COALESCE(a.published_at,a.created_at) DESC,a.id DESC
LIMIT ".$categoryLimit." OFFSET ".$categoryOffset);
$q->execute([$category['name'],$category['slug'],$category['id'],$category['id']]);
$articles=$q->fetchAll();

$pageTitle=$category['name'];
$pageDescription=rich_text_excerpt($category['description']??'',260) ?: 'Публикации рубрики «'.$category['name'].'» сетевого издания «АХИХЪАН».';
if($category['slug']==='istoriya'){
  $pageTitle='История Унцукульского района';
  $pageDescription='История Унцукульского района: Ахульго, старые изображения Унцукуля и Аракани, народный художественный промысел и историческая память района.';
}
$seoCanonical=$categoryPager['page']>1 ? category_url($category).'?page='.$categoryPager['page'] : category_url($category);
$categoryItemList=[];
foreach(array_slice($articles,0,20) as $index=>$item){
  $categoryItemList[]=[
    '@type'=>'ListItem',
    'position'=>$index+1,
    'url'=>article_url($item),
    'name'=>(string)$item['title'],
  ];
}
$seoJsonLd=[
  [
    '@context'=>'https://schema.org',
    '@type'=>'BreadcrumbList',
    'itemListElement'=>[
      ['@type'=>'ListItem','position'=>1,'name'=>'Главная','item'=>base_url()],
      ['@type'=>'ListItem','position'=>2,'name'=>(string)$category['name'],'item'=>$seoCanonical],
    ],
  ],
  [
    '@context'=>'https://schema.org',
    '@type'=>'CollectionPage',
    '@id'=>$seoCanonical.'#collection',
    'url'=>$seoCanonical,
    'name'=>(string)$category['name'].' — АХИХЪАН',
    'description'=>$pageDescription,
    'isPartOf'=>['@id'=>base_url('#website')],
    'mainEntity'=>[
      '@type'=>'ItemList',
      'numberOfItems'=>$categoryTotal,
      'itemListElement'=>$categoryItemList,
    ],
  ],
];
require __DIR__.'/partials/header.php';
?>
<div class="wrap page-shell">
  <div class="page-head">
    <div>
      <span class="heading-kicker">Рубрика</span>
      <h1><?=e($category['name'])?></h1>
      <p><?=e(rich_text_excerpt($category['description'] ?: 'Материалы сетевого издания «АХИХЪАН» о жизни Унцукульского района.',300))?></p>
    </div>
    <div class="page-head-mark" aria-hidden="true"></div>
  </div>

  <?php if($category['slug']==='istoriya'):?>
    <section class="history-feature" aria-labelledby="history-feature-title">
      <div class="history-feature-visual">
        <img src="https://upload.wikimedia.org/wikipedia/commons/5/5c/Grigory_Gagarin._Daghestan_septentrional._Ountzoukoul.jpg" alt="Унцукуль. Рисунок Григория Гагарина, опубликованный в 1847 году">
        <span class="history-image-badge">1847</span>
        <p class="history-photo-credit">
          Григорий Гагарин · «Ountzoukoul» · 1847 ·
          <a href="https://commons.wikimedia.org/wiki/File:Grigory_Gagarin._Daghestan_septentrional._Ountzoukoul.jpg" target="_blank" rel="noopener">Wikimedia Commons</a> · общественное достояние
        </p>
      </div>
      <div class="history-feature-copy">
        <span class="heading-kicker">Память места</span>
        <h2 id="history-feature-title">История, которая живёт в ландшафте</h2>
        <p>История Унцукульского района складывается из памяти горных селений, событий Кавказской войны, художественных традиций и семейных историй. Здесь находятся места, связанные с Ахульго, Гимрами, Ашильтой, Аракани и Унцукулем — названиями, которые давно вошли в историческую и культурную память Дагестана.</p>
        <p>Современный Унцукульский район образован в <strong>1935 году</strong>. Но культурная история этих мест значительно старше: традиция унцукульской насечки металлом по дереву, по данным материалов РИА «Дагестан», уходит корнями как минимум в XVII век.</p>
        <div class="history-feature-facts">
          <span><b>1935</b><small>образование района</small></span>
          <span><b>1839</b><small>события Ахульго</small></span>
          <span><b>XVII век</b><small>корни унцукульского промысла</small></span>
        </div>
      </div>
    </section>

    <section class="history-timeline-section">
      <div class="history-section-heading">
        <div>
          <span class="heading-kicker">Хронология</span>
          <h2>Ключевые страницы</h2>
        </div>
        <p>Краткий ориентир по событиям и культурным традициям, связанным с районом.</p>
      </div>

      <div class="history-timeline">
        <article>
          <span class="history-timeline-year">XVII</span>
          <div>
            <b>Унцукульская насечка</b>
            <p>Местный художественный промысел — инкрустация деревянных изделий металлической проволокой — стал одной из наиболее узнаваемых культурных традиций Унцукуля.</p>
          </div>
        </article>
        <article>
          <span class="history-timeline-year">1839</span>
          <div>
            <b>Ахульго</b>
            <p>Летом 1839 года на горе Ахульго проходила длительная осада укрепления имама Шамиля. Эти события стали одной из наиболее известных страниц Кавказской войны и исторической памяти Северного Кавказа.</p>
          </div>
        </article>
        <article>
          <span class="history-timeline-year">1847</span>
          <div>
            <b>Унцукуль и Аракани в работах Гагарина</b>
            <p>В альбоме «Живописный Кавказ» были опубликованы изображения местности и селений, выполненные князем Григорием Гагариным с натуры.</p>
          </div>
        </article>
        <article>
          <span class="history-timeline-year">1935</span>
          <div>
            <b>Образование района</b>
            <p>Современный Унцукульский район ведёт административную историю с 1935 года. В 2025 году район отмечал 90-летие.</p>
          </div>
        </article>
        <article>
          <span class="history-timeline-year">2017</span>
          <div>
            <b>Мемориальный комплекс «Ахульго»</b>
            <p>20 января 2017 года был открыт культурно-исторический комплекс «Ахульго» — филиал Национального музея Дагестана, посвящённый событиям Кавказской войны и памяти о них.</p>
          </div>
        </article>
      </div>
    </section>

    <section class="history-story-grid">
      <article class="history-story-card is-wide">
        <div class="history-story-image">
          <img src="https://upload.wikimedia.org/wikipedia/commons/1/1e/%D0%9C%D0%9A_%C2%AB%D0%90%D1%85%D1%83%D0%BB%D1%8C%D0%B3%D0%BE%C2%BB%2C_%D0%94%D0%B0%D0%B3%D0%B5%D1%81%D1%82%D0%B0%D0%BD.jpg" alt="Мемориальный комплекс Ахульго в Унцукульском районе" loading="lazy">
          <span>Ахульго</span>
        </div>
        <div class="history-story-copy">
          <span class="heading-kicker">Историческая память</span>
          <h3>Ахульго — место памяти</h3>
          <p>Комплекс на одноимённой горе был открыт в 2017 году в память о событиях лета 1839 года. В его экспозиции представлены материалы Кавказской войны, макет местности и копия панорамы Франца Рубо «Штурм аула Ахульго».</p>
          <a href="https://www.culture.ru/institutes/40368/kulturno-istoricheskii-kompleks-akhulgo-memorial-obshei-pamyati-i-obshei-sudby" target="_blank" rel="noopener">Подробнее на Культура.РФ →</a>
          <small>Фото: Vanlaf / Wikimedia Commons · CC BY-SA 4.0</small>
        </div>
      </article>

      <article class="history-story-card">
        <div class="history-story-image">
          <img src="https://upload.wikimedia.org/wikipedia/commons/d/de/Daghestan._Arakane_dans_le_Koissoubou_%D0%93%D1%80%D0%B8%D0%B3%D0%BE%D1%80%D0%B8%D0%B9_%D0%93%D0%B0%D0%B3%D0%B0%D1%80%D0%B8%D0%BD.jpg" alt="Аракани. Рисунок Григория Гагарина, 1847 год" loading="lazy">
          <span>Аракани · 1847</span>
        </div>
        <div class="history-story-copy">
          <span class="heading-kicker">Старый Дагестан</span>
          <h3>Аракани глазами художника XIX века</h3>
          <p>Работа Григория Гагарина сохраняет один из редких визуальных образов Аракани середины XIX века — архитектуру горного селения и характер его застройки.</p>
          <small>Григорий Гагарин · 1847 · общественное достояние</small>
        </div>
      </article>

      <article class="history-story-card history-craft-card">
        <div class="history-craft-ornament" aria-hidden="true">
          <span></span><span></span><span></span>
        </div>
        <div class="history-story-copy">
          <span class="heading-kicker">Наследие мастеров</span>
          <h3>Унцукульская насечка</h3>
          <p>Насечка металлом по дереву — один из художественных символов района. Деревянную поверхность украшают геометрическими композициями из мельхиоровой или серебряной проволоки. В XX веке промысел получил развитие в художественном производстве и преподавался молодёжи.</p>
          <a href="https://riadagestan.ru/news/interview/isa_nurmagomedov_untsukulskaya_ornamentalnaya_nasechka_eto_redkiy_vid_iskusstva_kotoryy_my_khotim_sokhranit" target="_blank" rel="noopener">Материал РИА «Дагестан» →</a>
        </div>
      </article>
    </section>

    <section class="history-sources">
      <span class="heading-kicker">Источники</span>
      <p>При подготовке исторической справки использованы материалы Культура.РФ, РИА «Дагестан» и Wikimedia Commons. Исторические изображения Григория Гагарина находятся в общественном достоянии; современное фото комплекса «Ахульго» опубликовано на Wikimedia Commons по лицензии CC BY-SA 4.0.</p>
    </section>

    <div class="history-publications-heading">
      <div>
        <span class="heading-kicker">Публикации «АХИХЪАН»</span>
        <h2>Материалы об истории района</h2>
      </div>
      <p><?=$categoryTotal?> <?=$categoryTotal===1?'материал':'материалов'?></p>
    </div>
  <?php endif;?>

  <?php if($articles): ?>
  <section class="listing-grid">
    <?php foreach($articles as $a): ?>
      <article class="list-card">
        <a class="list-thumb<?=empty($a['cover_image'])?' demo-road':''?>" href="<?=e(article_url($a))?>" style="<?=!empty($a['cover_image']) ? "background-image:url('".e(base_url($a['cover_image']))."')" : ''?>"></a>
        <div class="list-card-body">
          <div class="article-label"><?=e($a['category_name'] ?: $category['name'])?></div>
          <h2><a href="<?=e(article_url($a))?>"><?=e($a['title'])?></a></h2>
          <p><?=e(rich_text_excerpt($a['excerpt'],220))?></p>
          <div class="article-meta">
            <span><?=e(ru_date($a['published_at'] ?: $a['created_at']))?></span>
            <span>◉ <?=number_format((int)$a['views'],0,'.',' ')?></span>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </section>
  <?php render_public_pagination('category/'.rawurlencode((string)$category['slug']),$categoryPager['page'],$categoryPager['total_pages'],[],'page','Страницы рубрики'); ?>
  <?php else: ?>
    <?php if($category['slug']==='istoriya'):?>
      <div class="empty history-empty">Редакционные материалы об истории района будут появляться здесь по мере публикации.</div>
    <?php else:?>
      <div class="empty">В этой рубрике пока нет опубликованных материалов.</div>
    <?php endif;?>
  <?php endif; ?>
</div>
<?php require __DIR__.'/partials/footer.php'; ?>
