<?php
require __DIR__ . '/app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if($_SERVER['REQUEST_METHOD']!=='POST'){
    http_response_code(405);
    echo json_encode(['ok'=>false,'error'=>'Метод не поддерживается.'],JSON_UNESCAPED_UNICODE);
    exit;
}

try{
    $articleId=(int)($_POST['article_id']??0);
    $reaction=(string)($_POST['reaction']??'');
    $token=(string)($_POST['token']??'');
    $counts=update_article_reaction($articleId,$reaction,$token);

    echo json_encode([
        'ok'=>true,
        'likes'=>$counts['likes'],
        'dislikes'=>$counts['dislikes'],
        'score'=>$counts['score'],
        'reaction'=>$reaction,
    ],JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){
    http_response_code(422);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);
}
