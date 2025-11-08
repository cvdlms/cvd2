# TODO: Refactor question_bank.php

## Overview
Refactor the large question_bank.php file by splitting it into smaller, manageable components to improve code maintainability and reduce complexity.

## Steps to Complete

### 1. Extract PHP Logic Handlers
- [ ] Create `question_bank_handlers.php` for all POST request handlers (add, edit, delete questions, import/export).
- [ ] Move all PHP logic for handling forms and actions to this file.
- [ ] Include this file at the top of question_bank.php after session checks.

### 2. Extract HTML Components
- [ ] Create `question_bank_form.php` for the "Add Question Form" HTML section.
- [ ] Create `question_bank_list.php` for the questions display (accordion, tables, modals).
- [ ] Create `question_bank_import.php` for the import section (JSON and Excel import forms).
- [ ] Create `question_bank_modals.php` for all modals (delete confirmation, edit question, Excel import).

### 3. Extract JavaScript
- [ ] Create `question_bank.js` and move all inline JavaScript to this file.
- [ ] Update question_bank.php to include this JS file.

### 4. Update Main File
- [ ] Modify `question_bank.php` to include the new component files.
- [ ] Keep only the main structure, variable assignments, and includes.
- [ ] Ensure all variables are passed correctly to included files.

### 5. Test Functionality
- [ ] Test adding questions.
- [ ] Test editing questions.
- [ ] Test deleting questions.
- [ ] Test importing from JSON and Excel.
- [ ] Test exporting questions.
- [ ] Verify all JavaScript interactions work.

### 6. Clean Up
- [ ] Remove any redundant code.
- [ ] Ensure no syntax errors.
- [ ] Update any TODO comments if needed.
