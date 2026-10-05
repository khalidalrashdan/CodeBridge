<?php
declare(strict_types=1);require_once __DIR__.'/../config/bootstrap.php';requireLogin();header('Content-Type: application/json; charset=utf-8');$s=$pdo->query("SELECT full_name,xp,level,streak FROM users WHERE role='student' ORDER BY xp DESC,level DESC,full_name LIMIT 100");$rows=$s->fetchAll();foreach($rows as $i=>&$r)$r['rank']=$i+1;echo json_encode($rows);
