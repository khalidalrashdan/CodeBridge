<?php
require __DIR__ . '/config.php';

$a = $_GET['a'] ?? '';
$in = json_decode(file_get_contents('php://input'), true) ?? [];

function out($d, $code = 200) { http_response_code($code); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }
function user() {
    global $db;
    if (empty($_SESSION['uid'])) out(['error' => 'auth'], 401);
    $s = $db->prepare('SELECT id,name,email,role,lang,theme,xp,streak FROM users WHERE id=?');
    $s->execute([$_SESSION['uid']]);
    return $s->fetch() ?: out(['error' => 'auth'], 401);
}
function student() { $u = user(); if ($u['role'] !== 'student') out(['error' => 'forbidden'], 403); return $u; }
function level($xp) { return intdiv($xp, 100) + 1; }

switch ($a) {
case 'register':
    $name = trim($in['name'] ?? ''); $email = strtolower(trim($in['email'] ?? ''));
    $pass = $in['password'] ?? ''; $role = $in['role'] ?? 'student';
    if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 8 || !in_array($role, ['student', 'parent'], true))
        out(['error' => 'invalid'], 422);
    $child = null;
    if ($role === 'parent') {
        $s = $db->prepare("SELECT id FROM users WHERE email=? AND role='student'");
        $s->execute([strtolower(trim($in['child_email'] ?? ''))]);
        $child = $s->fetchColumn();
        if (!$child) out(['error' => 'child_not_found'], 422);
    }
    $s = $db->prepare('SELECT 1 FROM users WHERE email=?'); $s->execute([$email]);
    if ($s->fetch()) out(['error' => 'email_taken'], 409);
    $db->prepare('INSERT INTO users(name,email,password_hash,role,lang) VALUES(?,?,?,?,?)')
       ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role, ($in['lang'] ?? 'en') === 'ar' ? 'ar' : 'en']);
    $id = $db->lastInsertId();
    if ($child) $db->prepare('INSERT INTO parent_links VALUES(?,?)')->execute([$id, $child]);
    session_regenerate_id(true); $_SESSION['uid'] = $id;
    out(['ok' => true]);

case 'login':
    $s = $db->prepare('SELECT id,password_hash FROM users WHERE email=?');
    $s->execute([strtolower(trim($in['email'] ?? ''))]);
    $u = $s->fetch();
    if (!$u || !password_verify($in['password'] ?? '', $u['password_hash'])) out(['error' => 'bad_login'], 401);
    session_regenerate_id(true); $_SESSION['uid'] = $u['id'];
    out(['ok' => true]);

case 'logout': session_destroy(); out(['ok' => true]);

case 'me': out(user());

case 'lang':
    $u = user();
    $db->prepare('UPDATE users SET lang=? WHERE id=?')->execute([($in['lang'] ?? '') === 'ar' ? 'ar' : 'en', $u['id']]);
    out(['ok' => true]);

case 'theme':
    $u = user();
    $th = in_array($in['theme'] ?? '', ['f1', 'football', 'roblox'], true) ? $in['theme'] : '';
    $db->prepare('UPDATE users SET theme=? WHERE id=?')->execute([$th, $u['id']]);
    out(['ok' => true]);

case 'catalog':
    $u = student();
    $s = $db->prepare('SELECT lesson_id FROM progress WHERE student_id=?'); $s->execute([$u['id']]);
    $done = $s->fetchAll(PDO::FETCH_COLUMN);
    $s = $db->prepare('SELECT course,tutoring FROM enrollments WHERE student_id=?'); $s->execute([$u['id']]);
    $lessons = array_map(function ($l) { unset($l['pattern']); return $l; }, array_values($GLOBALS['LESSONS']));
    out(['lessons' => $lessons, 'done' => $done, 'enrolled' => $s->fetchAll(),
         'prices' => ['course' => COURSE_PRICE, 'tutoring' => TUTORING_PRICE]]);

case 'enroll': // Payment is simulated. Connect a gateway (e.g. Benefit Pay) before going live.
    $u = student(); $course = $in['course'] ?? '';
    if (!in_array($course, array_column($LESSONS, 'course'), true)) out(['error' => 'invalid'], 422);
    $t = !empty($in['tutoring']) ? 1 : 0;
    $db->prepare('INSERT IGNORE INTO enrollments(student_id,course,tutoring,amount_bhd) VALUES(?,?,?,?)')
       ->execute([$u['id'], $course, $t, COURSE_PRICE + $t * TUTORING_PRICE]);
    out(['ok' => true]);

case 'submit':
    $u = student(); $l = $LESSONS[$in['lesson_id'] ?? ''] ?? out(['error' => 'invalid'], 422);
    $first = array_values(array_filter($LESSONS, fn($x) => $x['course'] === $l['course']))[0]['id'];
    if ($l['id'] !== $first) { // only the first lesson of each course is a free preview
        $s = $db->prepare('SELECT 1 FROM enrollments WHERE student_id=? AND course=?');
        $s->execute([$u['id'], $l['course']]);
        if (!$s->fetch()) out(['error' => 'locked'], 402);
    }
    $code = preg_replace('/\s+/', '', strtolower($in['code'] ?? ''));
    $ok = (bool) preg_match($l['pattern'], $code);
    $xp = $u['xp']; $streak = 0; $gained = 0;
    if ($ok) {
        $ins = $db->prepare('INSERT IGNORE INTO progress(student_id,lesson_id) VALUES(?,?)');
        $ins->execute([$u['id'], $l['id']]);
        $streak = $u['streak'] + 1;
        if ($ins->rowCount()) { $gained = XP_PER_LESSON; $xp += $gained; }
    }
    $db->prepare('UPDATE users SET xp=?, streak=? WHERE id=?')->execute([$xp, $streak, $u['id']]);
    out(['correct' => $ok, 'gained' => $gained, 'xp' => $xp, 'streak' => $streak, 'level' => level($xp)]);

case 'leaderboard':
    user();
    out($db->query("SELECT u.name,u.xp,(SELECT COUNT(*) FROM progress p WHERE p.student_id=u.id) AS lessons
                    FROM users u WHERE u.role='student' ORDER BY u.xp DESC, lessons DESC LIMIT 20")->fetchAll());

case 'children':
    $u = user(); if ($u['role'] !== 'parent') out(['error' => 'forbidden'], 403);
    $s = $db->prepare('SELECT s.id,s.name,s.xp,s.streak FROM users s JOIN parent_links l ON l.student_id=s.id WHERE l.parent_id=?');
    $s->execute([$u['id']]);
    $kids = $s->fetchAll();
    foreach ($kids as &$k) {
        $p = $db->prepare('SELECT lesson_id,completed_at FROM progress WHERE student_id=? ORDER BY completed_at DESC');
        $p->execute([$k['id']]); $k['done'] = $p->fetchAll();
        $e = $db->prepare('SELECT course,tutoring,amount_bhd FROM enrollments WHERE student_id=?');
        $e->execute([$k['id']]); $k['enrollments'] = $e->fetchAll();
        $k['level'] = level($k['xp']); unset($k['id']);
    }
    out(['children' => $kids, 'totals' => array_count_values(array_column($LESSONS, 'course'))]);

default: out(['error' => 'not_found'], 404);
}
