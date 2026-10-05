<?php
declare(strict_types=1);require_once __DIR__.'/../config/bootstrap.php';requireRole('admin');header('Content-Type: application/json; charset=utf-8');$action=$_GET['action']??'stats';
if($action==='stats'){$out=[];foreach(['users','courses','lessons','questions','enrollments','tutoring_requests','payments'] as $t){$out[$t]=(int)$pdo->query("SELECT COUNT(*) FROM {$t}")->fetchColumn();}echo json_encode($out);exit;}
if($action==='users'){$s=$pdo->query('SELECT id,full_name,email,role,language,xp,level,created_at FROM users ORDER BY created_at DESC');echo json_encode($s->fetchAll());exit;}
