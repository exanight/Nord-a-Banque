<?php
declare(strict_types=1);
// Seul ce repertoire public/ doit etre expose par Apache.
ini_set('display_errors','0');
header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");
date_default_timezone_set('Europe/Paris');
foreach(['Http','Database','Directory','Auth','Policy','Service'] as $class)require dirname(__DIR__).'/src/'.$class.'.php';
try {
    if(($_SERVER['HTTPS']??'')!=='on')throw new HttpError(400,'HTTPS est obligatoire pour l’espace équipe.');
    ini_set('session.use_strict_mode','1');ini_set('session.use_only_cookies','1');
    session_name('nordea_session');session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>true,'httponly'=>true,'samesite'=>'Strict']);session_start();
    $file=dirname(__DIR__,2).'/config/local.php';
    if(!is_file($file))throw new HttpError(503,'Le service doit être configuré par le responsable informatique.');
    $config=require $file;$db=new Database($config);$auth=new Auth($db,new Directory($config),$config);
    $route=trim((string)($_GET['route']??''),'/');$method=$_SERVER['REQUEST_METHOD'];
    if(!in_array($method,['GET','POST','PATCH'],true)){header('Allow: GET, POST, PATCH');throw new HttpError(405,'Méthode non autorisée.');}
    if($method!=='GET')Auth::checkCsrf();
    if($route==='login'&&$method==='POST')Http::json($auth->login(Http::body()));
    if($route==='logout'&&$method==='POST'){Auth::logout();Http::json(['deconnecte'=>true]);}
    if($route==='session'&&$method==='GET')Http::json(['user'=>empty($_SESSION['guid'])?null:$auth->user(),'csrf'=>Auth::csrf()]);
    $policy=new Policy($db,$auth->user());$service=new Service($db,$policy);
    if($route==='bootstrap'&&$method==='GET')Http::json($service->bootstrap());
    if(preg_match('~^(clients|appointments|requests)(?:/([1-9][0-9]*))?$~D',$route,$matches)&&$method==='GET')Http::json($service->read($matches[1],isset($matches[2])?Http::id($matches[2]):null));
    if($route==='clients'&&$method==='POST')Http::json($service->client(Http::body(),null),201);
    if(preg_match('~^clients/([1-9][0-9]*)$~D',$route,$matches)&&$method==='PATCH')Http::json($service->client(Http::body(),Http::id($matches[1])));
    if($route==='appointments'&&$method==='POST')Http::json($service->appointment(Http::body(),null),201);
    if(preg_match('~^appointments/([1-9][0-9]*)$~D',$route,$matches)&&$method==='PATCH')Http::json($service->appointment(Http::body(),Http::id($matches[1])));
    if($route==='requests'&&$method==='POST')Http::json($service->request(Http::body()),201);
    if(preg_match('~^requests/([1-9][0-9]*)/transitions$~D',$route,$matches)&&$method==='POST')Http::json($service->transition(Http::id($matches[1]),Http::body()),201);
    throw new HttpError(404,'Route introuvable.');
} catch(Throwable $error) {
    if(isset($db)&&$db->pdo->inTransaction())$db->pdo->rollBack();
    $status=$error instanceof HttpError?$error->status:500;$message=$error instanceof HttpError?$error->getMessage():'Le service est temporairement indisponible.';
    if($error instanceof PDOException&&($error->errorInfo[1]??null)===1062){$status=409;$message='Ce créneau est déjà réservé. Choisissez-en un autre.';}
    if($status===500)error_log('Nordea: erreur interne de type '.get_class($error));
    if($status===429)header('Retry-After: 900');
    http_response_code($status);header('Content-Type: application/json; charset=utf-8');echo json_encode(['error'=>['status'=>$status,'message'=>$message]],JSON_UNESCAPED_UNICODE);
}
