# Backend restructure notes

## Storage direction

- Move core business data from scattered JSON files to MySQL/MariaDB.
- Keep JSON only for small static configuration, templates, import/export, cache, and backups.
- Build the new version in a parallel folder such as `cvdlms-v2` instead of breaking the current `cvdlms` app in place.

## Data to migrate to database

- Users, teachers, students, classes, subjects.
- Teacher-class and teacher-subject assignments.
- Question banks, topics, units, questions, options, answers.
- Generated exams, exam questions, exam attempts, answers, and scores.
- Assignments, assignment classes, submissions, grading.
- Premium packages, keys, orders, subscriptions, requests.
- Notifications, login attempts, audit logs.

## Suggested migration phases

1. Back up current JSON data and uploads.
2. Audit JSON files for invalid data, duplicate IDs, empty files, and encoding issues.
3. Create a repository/storage layer so PHP pages stop reading and writing JSON directly.
4. Design MySQL schema and migrations with `utf8mb4`.
5. Import master data first: users, classes, subjects, students.
6. Import teacher mappings.
7. Import question banks.
8. Import exams, attempts, and scores.
9. Import assignments, submissions, premium, and notifications.
10. Switch pages/APIs to database-backed repositories module by module.
11. Keep legacy JSON read-only until DB output is verified.

## Immediate principle

Do not do a big-bang rewrite. Keep the existing system running, migrate one module at a time, and verify counts and behavior after each phase.
