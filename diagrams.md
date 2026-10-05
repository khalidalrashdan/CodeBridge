# CodeBridge Diagrams

## Use Case
Actors: Student, Parent, Tutor, Administrator, Payment Service.

Student: register, login, choose language, browse courses, enroll, study lesson, submit code, earn XP, view progress, leaderboard, achievements, request tutoring, message tutor, print certificate.

Parent: register, login, link student, view progress.

Tutor: login, view tutoring requests, schedule sessions, complete sessions, message students.

Administrator: login, view users, monitor courses, view platform statistics.

Payment Service: process course/tutoring payment and return verified status.

## Sequence: Coding Challenge
1. Student opens dashboard.
2. Frontend requests lessons from Course API.
3. Course API checks enrollment in MySQL.
4. Course API returns explanation and question.
5. Student clicks Next.
6. Frontend shows blank code editor.
7. Student submits code.
8. Course API normalizes/evaluates the answer.
9. API stores answer and attempts.
10. If correct, API updates XP, level, streak and progress.
11. Frontend displays feedback.

## Class Diagram
User <|-- Student
User <|-- Parent
User <|-- Tutor
User <|-- Admin
Course "1" -- "many" Lesson
Lesson "1" -- "many" Question
Student "many" -- "many" Course : Enrollment
Student "1" -- "many" Answer
Student "1" -- "many" Progress
Student "many" -- "many" Achievement
Student "1" -- "many" TutoringRequest
Student "1" -- "many" Payment
User "1" -- "many" Message : sender/receiver

## Architecture
Browser -> PHP pages -> JSON APIs -> PDO -> MySQL.

## Activity
Login -> Dashboard -> Select Course -> Enroll -> Explanation -> Next -> Question -> Code -> Submit -> Evaluate -> Correct? -> XP/Progress -> Next Lesson -> Course Complete -> Certificate.
