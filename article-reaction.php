<?php
require __DIR__ . '/app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow, noarchive');

if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['ok'=>false,'error'=>'Метод не поддерживается.'],JSON_UNESCAPED_UNICODE);
    exit;
}

if((int)($_SERVER['CONTENT_LENGTH']??0)>8192){
    http_response_code(413);
    echo json_encode(['ok'=>false,'error'=>'Слишком большой запрос.'],JSON_UNESCAPED_UNICODE);
    exit;
}

require_same_origin_post();

$rate=security_rate_limit('article-reaction-ip',security_client_ip(),30,60,120,true);
if(!empty($rate['blocked'])){
    http_response_code(429);
    header('Retry-After: '.max(1,(int)$rate['remaining']));
    echo json_encode(['ok'=>false,'error'=>'Слишком много запросов. Повторите позже.'],JSON_UNESCAPED_UNICODE);
    exit;
}

try{
    $articleId=(int)($_POST['article_id']??0);
    $reaction=(string)($_POST['reaction']??'');
    $token=(string)($_POST['token']??'');

    $tokenRate=security_rate_limit(
        'article-reaction-token',
        hash('sha256',$token).'|'.$articleId,
        8,
        60,
        120,
        true
    );
    if(!empty($tokenRate['blocked'])){
        http_response_code(429);
        header('Retry-After: '.max(1,(int)$tokenRate['remaining']));
        echo json_encode(['ok'=>false,'error'=>'Слишком частое изменение оценки.'],JSON_UNESCAPED_UNICODE);
        exit;
    }

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
    echo json_encode(['ok'=>false,'error'=>'Не удалось сохранить оценку.'],JSON_UNESCAPED_UNICODE);
}
