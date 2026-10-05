<?php
declare(strict_types=1);
require_once __DIR__.'/../config/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
requireLogin();
$action=$_POST['action']??$_GET['action']??''; $uid=userId(); $language=lang();
function localField(array $r,string $base,string $language):string{return $r[$base.'_'.$language]??$r[$base.'_en']??'';}
if($action==='list'){
 $s=$pdo->query('SELECT * FROM courses ORDER BY id');$rows=$s->fetchAll();
 foreach($rows as &$r){$r['title']=localField($r,'title',$language);$r['description']=localField($r,'description',$language);}
 echo json_encode($rows);exit;
}
if($action==='enroll'){
 checkCsrf($_POST['csrf']??null); if(role()!=='student'){http_response_code(403);exit;}
 $cid=(int)($_POST['course_id']??0);$tut=!empty($_POST['tutoring']);
 $s=$pdo->prepare('SELECT * FROM courses WHERE id=?');$s->execute([$cid]);$c=$s->fetch();if(!$c){http_response_code(404);echo json_encode(['message'=>'Course not found']);exit;}
 $amount=(float)$c['price_bhd']+($tut?10:0);$pdo->beginTransaction();
 try{
  $s=$pdo->prepare("INSERT INTO enrollments(student_id,course_id,amount_bhd,status) VALUES(?,?,?,'paid') ON DUPLICATE KEY UPDATE amount_bhd=VALUES(amount_bhd),status='paid'");$s->execute([$uid,$cid,$amount]);
  $tr=null;$ptype='course';
  if($tut){$s=$pdo->prepare("INSERT INTO tutoring_requests(student_id,course_id,amount_bhd,status) VALUES(?,?,10,'requested')");$s->execute([$uid,$cid]);$tr=(int)$pdo->lastInsertId();$ptype='course_and_tutoring';}
  $ref='CB-'.strtoupper(bin2hex(random_bytes(5)));$s=$pdo->prepare("INSERT INTO payments(student_id,course_id,tutoring_request_id,amount_bhd,payment_type,status,reference_code,paid_at) VALUES(?,?,?,?,?,'paid',?,NOW())");$s->execute([$uid,$cid,$tr,$amount,$ptype,$ref]);
  $s=$pdo->prepare('SELECT COUNT(*) FROM lessons WHERE course_id=?');$s->execute([$cid]);$total=(int)$s->fetchColumn();
  $s=$pdo->prepare('INSERT INTO progress(student_id,course_id,total_lessons) VALUES(?,?,?) ON DUPLICATE KEY UPDATE total_lessons=VALUES(total_lessons)');$s->execute([$uid,$cid,$total]);$pdo->commit();
  echo json_encode(['success'=>true,'amount'=>$amount,'reference'=>$ref]);
 }catch(Throwable $e){$pdo->rollBack();http_response_code(500);echo json_encode(['success'=>false,'message'=>'Enrollment failed.']);} exit;
}
if($action==='lesson'){
 $cid=(int)($_GET['course_id']??0);$s=$pdo->prepare("SELECT 1 FROM enrollments WHERE student_id=? AND course_id=? AND status='paid'");$s->execute([$uid,$cid]);if(!$s->fetch()){http_response_code(403);echo json_encode(['message'=>'Please enroll first.']);exit;}
 $s=$pdo->prepare('SELECT l.id,l.lesson_order,l.title_en,l.title_ar,l.explanation_en,l.explanation_ar,q.id question_id,q.prompt_en,q.prompt_ar,q.xp_reward FROM lessons l LEFT JOIN questions q ON q.lesson_id=l.id WHERE l.course_id=? ORDER BY l.lesson_order,q.question_order');$s->execute([$cid]);$rows=$s->fetchAll();foreach($rows as &$r){$r['title']=localField($r,'title',$language);$r['explanation']=localField($r,'explanation',$language);$r['prompt']=localField($r,'prompt',$language);}echo json_encode($rows);exit;
}
if($action==='submit'){
 checkCsrf($_POST['csrf']??null); if(role()!=='student')exit; $qid=(int)($_POST['question_id']??0);$answer=trim($_POST['answer']??'');
 $s=$pdo->prepare('SELECT q.*,l.course_id FROM questions q JOIN lessons l ON l.id=q.lesson_id WHERE q.id=?');$s->execute([$qid]);$q=$s->fetch();if(!$q){http_response_code(404);exit;}
 $norm=fn($x)=>strtolower(preg_replace('/\s+/','',trim((string)$x)));$correct=$answer!=='' && $norm($answer)===$norm($q['expected_answer']);
 $s=$pdo->prepare('INSERT INTO answers(student_id,question_id,answer,is_correct) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE answer=VALUES(answer),is_correct=VALUES(is_correct),attempts=attempts+1,answered_at=CURRENT_TIMESTAMP');$s->execute([$uid,$qid,$answer,$correct?1:0]);
 if(!$correct){$pdo->prepare('UPDATE users SET streak=0 WHERE id=?')->execute([$uid]);echo json_encode(['success'=>true,'correct'=>false,'message'=>$language==='ar'?'حاول مرة أخرى.':'Try again.']);exit;}
 $xp=(int)$q['xp_reward'];$pdo->prepare('UPDATE users SET xp=xp+?, level=FLOOR((xp+?)/100)+1, streak=streak+1 WHERE id=?')->execute([$xp,$xp,$uid]);
 $cid=(int)$q['course_id'];$s=$pdo->prepare("SELECT COUNT(DISTINCT l.id) FROM lessons l JOIN questions q ON q.lesson_id=l.id JOIN answers a ON a.question_id=q.id WHERE l.course_id=? AND a.student_id=? AND a.is_correct=1");$s->execute([$cid,$uid]);$completed=(int)$s->fetchColumn();$s=$pdo->prepare('SELECT COUNT(*) FROM lessons WHERE course_id=?');$s->execute([$cid]);$total=(int)$s->fetchColumn();$pct=$total?($completed/$total)*100:0;$pdo->prepare('UPDATE progress SET completed_lessons=?,percent_complete=? WHERE student_id=? AND course_id=?')->execute([$completed,$pct,$uid,$cid]);
 // Unlock achievements using the student's current XP and completed-course state.
 $s=$pdo->prepare('SELECT xp,streak FROM users WHERE id=?');$s->execute([$uid]);$stats=$s->fetch();
 $rules=[['beginner',10],['first-code',25],['on-fire',75],['course-master',100]];
 foreach($rules as [$slug,$needed]){if((int)$stats['xp'] >= $needed){$q=$pdo->prepare('SELECT id FROM achievements WHERE slug=?');$q->execute([$slug]);$aid=$q->fetchColumn();if($aid){$pdo->prepare('INSERT IGNORE INTO student_achievements(student_id,achievement_id) VALUES(?,?)')->execute([$uid,$aid]);}}}
 echo json_encode(['success'=>true,'correct'=>true,'xp'=>$xp,'message'=>$language==='ar'?'إجابة صحيحة!':'Correct!']);exit;
}
http_response_code(400);echo json_encode(['message'=>'Unknown action.']);
