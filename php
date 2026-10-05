<?php
// Edit these to match your MySQL setup (XAMPP defaults shown).
const DB_HOST = 'localhost', DB_NAME = 'codebridge', DB_USER = 'root', DB_PASS = '';
const COURSE_PRICE = 25, TUTORING_PRICE = 10, XP_PER_LESSON = 25;

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();
header('Content-Type: application/json; charset=utf-8');

$db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// Lessons: [id, course, [title en, ar], [explanation en, ar], example code, [question en, ar], answer regex]
// The regex runs on the student's code after lowercasing and removing all whitespace.
$LESSONS = [];
foreach (require __DIR__ . '/lessons.php' as $l) {
    $LESSONS[$l[0]] = ['id' => $l[0], 'course' => $l[1], 'title' => $l[2], 'explain' => $l[3],
                       'example' => $l[4], 'question' => $l[5], 'pattern' => $l[6]];
}
