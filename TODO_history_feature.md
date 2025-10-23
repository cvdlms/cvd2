# TODO: Add "View History" Feature for Student Exam Results

## Tasks
- [x] Create `teacher/api/get_student_history.php`: PHP script to handle GET/POST requests for student_code, read JSON file, sort by timestamp descending, return JSON.
- [x] Create `teacher/student_history.html`: HTML page with input for student_code, AJAX call to API, display table with columns: Test Name, Exam Type, Attempt, Score, Total Questions, Correct Answers, Timestamp.
- [x] Modify `teacher/manage_result.php`: Add "View History" button/link in the student table for each row, linking to student_history.html with student_code parameter.
- [x] Change from separate page to modal popup in manage_result.php for better UX.
- [x] Add Chart.js chart for scores over time in modal.
- [x] Add filter by exam type (TX1, TX2, GK, CK) in modal.
- [x] Test the functionality: Load history for a student, verify sorting and display.
