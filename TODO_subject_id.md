# Add subject_id to Exam Creation and Student Scores

## Task Overview
Add subject_id to exam creation process and ensure it's saved in student_score.json for filtering purposes (to show scores only for subjects taught by the teacher and classes they teach).

## Current Status
- Exam creation already includes subject_id in exam JSON files
- student_score.json has subject_id field but it's empty ("")
- submit_exam.php does not include subject_id in the saved result

## Plan
1. ✅ Modify submit_exam.php to extract subject_id from exam_id and include it in examResult
2. Update existing student_score.json entries to populate subject_id (if possible from exam data)
3. Test that new exam submissions include subject_id

## Files to Modify
- ✅ cvd2/student/api/submit_exam.php: Add subject_id extraction and inclusion
- cvd2/shared/scores/student_score.json: Update existing entries if possible

## Followup
- Implement dashboard filtering by subject_id (separate task)
- Verify scores are filtered correctly in teacher views
