# TODO: Implement KaTeX for Math Formula Rendering in Exam Page

## Tasks
- [x] Replace MathJax scripts in exam.php head with KaTeX CSS and JS links
- [x] Modify renderQuestions function to call KaTeX auto-render after inserting HTML
- [x] Test that formulas display correctly and persist on page refresh
- [x] Ensure no changes to UI structure

## Notes
- Use $ for inline math and $$ for display math as per JSON examples
- KaTeX will handle rendering automatically with auto-render script
