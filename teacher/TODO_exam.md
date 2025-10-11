# Enhance Exam Creation Feature

## Tasks
- [x] Add "Tên đề kiểm tra" input field to creation forms (manual and auto tabs)
- [x] Modify exam saving logic to use exams/{grade}/subject_{id}/{test_name}.json structure
- [ ] Add validation for test name (required, unique per subject/grade)
- [x] Add "Quản lý đề kiểm tra" tab to list existing exams
- [x] Implement exam listing with table showing name, date, teacher, questions count, status
- [x] Add action buttons: View, Edit, Delete, Approve
- [x] Implement View functionality: display exam details in a modal or section
- [x] Implement Edit functionality: show alert (full edit to be implemented later)
- [x] Implement Delete functionality: confirm and remove exam file
- [x] Implement Approve functionality: set approved status
- [ ] Update exam view section to handle multiple exams (dropdown selector)
- [ ] Test creating multiple exams
- [ ] Test all management actions
