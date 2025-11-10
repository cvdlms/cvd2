# TODO: Implement Lucky Wheel Spin Feature

## Overview
Implement a lucky wheel spin feature for teachers to randomly select a student from a chosen class. The interface should be cute and student-friendly, with animated icons, confetti effects, and a congratulatory popup.

## Steps
- [x] Update teacher.php: Add a new card for "Vòng Quay May Mắn" linking to lucky_wheel.php
- [x] Create teacher/lucky_wheel.php: New page with class selection dropdown, wheel display, spin button
- [x] Implement wheel drawing using Canvas API
- [x] Add student loading via API (get_students.php with class_id)
- [x] Implement spin animation using GSAP for smooth rotation
- [x] Add confetti effect using Canvas Confetti after selection
- [x] Use SweetAlert2 for congratulatory popup: "Chúc mừng, [Student Name] được chọn!"
- [x] Style with cute colors, Font Awesome icons, emojis, and animations
- [x] Ensure only assigned classes are shown for the teacher
- [ ] Test random selection logic and animations
- [ ] Add necessary CDN links for libraries: GSAP, Canvas Confetti, SweetAlert2, Font Awesome

## Libraries to Add
- GSAP (for animations): https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js
- Canvas Confetti: https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js
- SweetAlert2: https://cdn.jsdelivr.net/npm/sweetalert2@11
- Font Awesome: https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css

## Dependent Files
- teacher.php (add card)
- teacher/lucky_wheel.php (new file)
- api/get_students.php (existing, ensure it works with class_id)
- api/get_classes.php (existing)

## Followup
- Test on different browsers
- Ensure mobile responsiveness
- Verify teacher class restrictions
