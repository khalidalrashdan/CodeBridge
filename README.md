# CodeBridge

CodeBridge is a senior-project full-stack coding-learning platform for the University of Bahrain College of Information Technology.

## Stack
- PHP 8+
- MySQL 8+
- HTML5
- CSS3
- Vanilla JavaScript
- PDO
- PHP sessions

## Features
- Student, parent, tutor and admin roles
- English/Arabic UI foundation with RTL support
- Courses: HTML, CSS, JavaScript, PHP, Java
- 25 BHD course price
- Optional 10 BHD one-to-one tutoring request
- Explanation -> Next -> Question lesson flow
- Blank code-entry textarea
- XP, levels and streaks
- Leaderboard
- Achievements
- Parent progress tracking through student link code
- Tutor request management
- Basic messaging API/page
- Admin statistics and user list
- Printable completion certificate
- MySQL persistence
- CSRF token protection for state-changing API requests
- Password hashing with password_hash()

## Important prototype note
The enrollment endpoint marks a payment as `paid` to demonstrate the senior-project flow. It is NOT a real payment gateway. For production, replace this with a Bahrain-supported payment provider and verify the provider's webhook/server response before setting a payment to `paid`.

## XAMPP setup
1. Install XAMPP.
2. Copy this folder into `C:\xampp\htdocs\CodeBridge`.
3. Start Apache and MySQL.
4. Open phpMyAdmin.
5. Import `database/schema.sql`.
6. Open `http://localhost/CodeBridge/public/`.

## Database configuration
Edit `backend/config/database.php` if your MySQL credentials differ from:
- host: 127.0.0.1
- database: codebridge
- user: root
- password: empty

## GitHub
Do NOT commit real passwords, API keys, payment secrets, or production database credentials.
Use the included `.gitignore`.

## Suggested academic modules
The database and role structure are designed to support:
- requirements analysis
- use cases
- ER/database design
- class diagram
- sequence diagram
- activity diagram
- implementation
- usability testing
- progress and learning analytics
