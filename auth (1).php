<?php
declare(strict_types=1);
require_once __DIR__.'/../config/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
$action=$_POST['action']??'';
if ($action==='register') {
    checkCsrf($_POST['csrf']??null);
    $name=trim($_POST['full_name']??''); $email=strtolower(trim($_POST['email']??''));
    $password=$_POST['password']??''; $r=$_POST['role']??'student'; $language=($_POST['language']??'en')==='ar'?'ar':'en';
    if($name==='' || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($password)<8 || !in_array($r,['student','parent'],true)) { http_response_code(422); echo json_encode(['success'=>false,'message'=>'Invalid registration details.']); exit; }
    $s=$pdo->prepare('SELECT id FROM users WHERE email=?'); $s->execute([$email]);
    if($s->fetch()){http_response_code(409);echo json_encode(['success'=>false,'message'=>'Email already exists.']);exit;}
    $linkCode = $r === 'student' ? strtoupper(bin2hex(random_bytes(4))) : null;
    $s=$pdo->prepare('INSERT INTO users(full_name,email,password_hash,role,language,link_code) VALUES(?,?,?,?,?,?)'); $s->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),$r,$language,$linkCode]);
    session_regenerate_id(true); $_SESSION['user_id']=(int)$pdo->lastInsertId(); $_SESSION['role']=$r; $_SESSION['language']=$language;
    echo json_encode(['success'=>true,'role'=>$r]); exit;
}
if($action==='login'){
    checkCsrf($_POST['csrf']??null); $email=strtolower(trim($_POST['email']??'')); $password=$_POST['password']??'';
    $s=$pdo->prepare('SELECT * FROM users WHERE email=?');$s->execute([$email]);$u=$s->fetch();
    if(!$u || !password_verify($password,$u['password_hash'])){http_response_code(401);echo json_encode(['success'=>false,'message'=>'Invalid email or password.']);exit;}
    session_regenerate_id(true); $_SESSION['user_id']=(int)$u['id'];$_SESSION['role']=$u['role'];$_SESSION['language']=$u['language'];
    echo json_encode(['success'=>true,'role'=>$u['role']]);exit;
}
if($action==='logout'){
    checkCsrf($_POST['csrf']??null); $_SESSION=[]; if(ini_get('session.use_cookies')){ $p=session_get_cookie_params(); setcookie(session_name(),' ',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']); } session_destroy(); echo json_encode(['success'=>true]);exit;
}
http_response_code(400);echo json_encode(['success'=>false,'message'=>'Unknown action.']);
