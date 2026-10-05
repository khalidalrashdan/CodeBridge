<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
    ]);
    session_start();
}

function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: index.php?page=login');
        exit;
    }
}
function requireRole(string $role): void {
    requireLogin();
    if (($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403); exit('Access denied.');
    }
}
function userId(): int { return (int)($_SESSION['user_id'] ?? 0); }
function role(): string { return (string)($_SESSION['role'] ?? ''); }
function lang(): string { return ($_SESSION['language'] ?? 'en') === 'ar' ? 'ar' : 'en'; }
function csrfToken(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function checkCsrf(?string $token): void {
    if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(419); exit('Invalid security token.');
    }
}
function redirectHome(): void {
    $r=role();
    if($r==='parent') $target='index.php?page=parent'; elseif($r==='tutor') $target='index.php?page=tutor'; elseif($r==='admin') $target='index.php?page=admin'; else $target='index.php?page=dashboard'; header('Location: '.$target);
    exit;
}
function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
