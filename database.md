# Database Tables

users, parent_student, courses, lessons, questions, enrollments, answers, progress, achievements, student_achievements, tutoring_requests, payments, messages.

Foreign keys enforce relationships. `student_course` and `student_course_progress` are unique to prevent duplicate enrollment/progress rows.
