<?php require_once __DIR__.'/../backend/config/auth.php'; $page=$_GET['page']??'home'; if(in_array($page,['dashboard','courses','lesson','leaderboard','parent','tutor','admin','messages','profile','achievements','certificate'],true)) requireLogin(); ?>
<!doctype html><html lang="<?=h(lang())?>" dir="<?=lang()==='ar'?'rtl':'ltr'?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>CodeBridge</title><link rel="stylesheet" href="assets/css/style.css"><script>window.CSRF='<?=h(csrfToken())?>';</script></head><body>
<nav class="nav"><a class="logo" href="index.php">💻 CodeBridge</a><div class="actions"><?php if(empty($_SESSION['user_id'])): ?><a class="btn secondary" href="index.php?page=login">Login</a><a class="btn" href="index.php?page=register">Register</a><?php else: ?><a class="btn secondary" href="index.php?page=dashboard">Dashboard</a><button class="btn secondary" onclick="logout()">Logout</button><?php endif; ?></div></nav>
<main class="container"><?php
switch($page){
case 'login': include __DIR__.'/pages/login.php'; break;
case 'register': include __DIR__.'/pages/register.php'; break;
case 'dashboard': include __DIR__.'/pages/dashboard.php'; break;
case 'courses': include __DIR__.'/pages/courses.php'; break;
case 'lesson': include __DIR__.'/pages/lesson.php'; break;
case 'leaderboard': include __DIR__.'/pages/leaderboard.php'; break;
case 'parent': include __DIR__.'/pages/parent.php'; break;
case 'tutor': include __DIR__.'/pages/tutor.php'; break;
case 'admin': include __DIR__.'/pages/admin.php'; break;
case 'messages': include __DIR__.'/pages/messages.php'; break;
case 'profile': include __DIR__.'/pages/profile.php'; break;
case 'achievements': include __DIR__.'/pages/achievements.php'; break;
case 'certificate': include __DIR__.'/pages/certificate.php'; break;
default: ?><section class="hero"><h1>💻 CodeBridge</h1><h2>Learn. Code. Level Up. 🚀</h2><p>A structured coding-learning platform for students in Bahrain.</p><div class="actions"><a class="btn" href="index.php?page=register">Start Learning</a><a class="btn secondary" href="index.php?page=courses">Explore Courses</a></div></section><br><div id="courses" class="grid"></div><?php } ?></main>
<script src="assets/js/app.js"></script><script>
<?php if($page==='home'): ?>
(async()=>{try{const cs=await api('backend/api/course.php?action=list');document.getElementById('courses').innerHTML=cs.map(c=>`<div class="card"><h2>${escapeHTML(c.title)}</h2><p>${escapeHTML(c.description)}</p><div class="price">${c.price_bhd} BHD</div></div>`).join('')}catch(e){}})();
<?php endif; ?>
</script></body></html>
