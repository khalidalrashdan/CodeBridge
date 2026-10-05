<?php
declare(strict_types=1);

$host = getenv('CODEBRIDGE_DB_HOST') ?: '127.0.0.1';
$db   = getenv('CODEBRIDGE_DB_NAME') ?: 'codebridge';
$user = getenv('CODEBRIDGE_DB_USER') ?: 'root';
$pass = getenv('CODEBRIDGE_DB_PASS') ?: '';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed. Check your MySQL settings.');
}
