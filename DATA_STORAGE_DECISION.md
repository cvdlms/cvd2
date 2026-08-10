# Quyết định lưu trữ — chọn (a) hay (b)

Ngày ghi: 2026-08-09
Trạng thái: ĐÃ CHỌN (a) — 2026-08-10.

## Bối cảnh
- Hệ thống lưu dữ liệu dạng JSON (file), điểm thi ghi vào:
  - File cá nhân: `shared/scores/<ma_HS>.json` (mỗi học sinh một file).
  - File tổng hợp: `shared/scores/student_score.json` (dùng chung cho tất cả).
  - Luyện tập: `data/practice_results/practice_results.json`.
- Khi 50 HS cùng lớp nộp bài cùng lúc, file tổng hợp `student_score.json` bị race
  "đọc–sửa–ghi" → mất mục (lost update). Đã xác nhận bằng test: 30 tiến trình ghi
  đồng thời chỉ còn 7/30 mục.

## Đã làm (2026-08-09)
- Thêm `update_json_data()` trong `includes/json_db_helper.php` (khóa `flock` + atomic write).
- Áp dụng khóa cho các luồng ghi:
  - `shared/api/scores.php` → `saveExamResult()` (file cá nhân + file tổng hợp).
  - `student/api/submit_exam.php` → ghi `practice_results.json`.
  - `teacher/api/update_note.php` → sửa ghi chú trên `student_score.json`.
  - `admin/api/delete_results.php` → xóa kết quả.
- Kiểm chứng: 30 tiến trình ghi đồng thời → 30/30 mục (hết mất dữ liệu).

## Vấn đề còn mở (chọn 1 trong 2)
### Phương án (a) — File cá nhân là nguồn chuẩn + rebuild chỉ mục theo yêu cầu
- File cá nhân mỗi học sinh là nguồn dữ liệu chính (đã cách ly sẵn, zero tranh chấp).
- `student_score.json` chỉ là chỉ mục tạm, **rebuild lại** (bằng `shared/api/rebuild_student_score.php`)
  mỗi khi giáo viên mở trang "Quản lý kết quả".
- Ưu: ít đụng code, hiệu quả với quy mô hiện tại, bỏ tranh chấp trên file tổng hợp.
- Nhược: giáo viên xem phải chờ rebuild (nhanh với dữ liệu nhỏ); cần cơ chế rebuild tự động/hợp nhất.

## ĐÃ TRIỂN KHAI (2026-08-10) — phương án (a)
- File mới `includes/score_index.php`:
  - `score_index_is_stale()`: so mtime `student_score.json` với mọi file cá nhân, bỏ qua file phụ
    (chỉ mục, backup, *.old, *_backup).
  - `rebuild_student_score_index($force=false)`: ghép nhóm bản ghi theo `(student, exam, subject)`,
    giữ bản ghi mới nhất (theo timestamp), cộng `attempts`, ghi đè toàn bộ chỉ mục bên trong khóa
    độc quyền (cùng `.lock` với `saveExamResult`) nên không cạnh tranh với luồng nộp bài.
  - Ghi chú giáo viên được **giữ qua rebuild**: ưu tiên ghi chú ở chỉ mục cũ (bản ghi cũ chỉ lưu ở
    đó), nếu không có thì lấy từ file cá nhân.
  - Xử lý cả file cá nhân dạng mảng lẫn dạng object đơn (file cũ).
- `teacher/manage_result.php` và `admin/api/get_all_results.php` gọi `rebuild_student_score_index()`
  trước khi đọc → trang luôn khớp 100% với file cá nhân mà không cần thao tác tay.
- `teacher/api/update_note.php` nay ghi chú vào **cả file cá nhân** (nguồn chuẩn) để không mất khi rebuild.
- `shared/api/rebuild_student_score.php` giữ vai trò rebuild thủ công (`?force=1`).
- Kiểm chứng: rebuild cho ra 19/19 mục khớp file cá nhân (0 lệch); thêm bản ghi mới → `is_stale()=true`
  → rebuild tự gộp, lần chạy sau (chỉ mục đã mới) tự bỏ qua.

### Phương án (b) — Chuyển sang cơ sở dữ liệu (MySQL/MariaDB hoặc SQLite)
- Dùng transaction, xử lý đồng thời tự nhiên, hết hẳn loại vấn đề file JSON.
- Theo định hướng đã ghi trong `BACKEND_RESTRUCTURE_NOTES.md` (chuyển dần sang MySQL).
- Nhược: refactor lớn, cần migration dữ liệu, làm dần từng module.

## Ghi chú bổ sung
- Đề xuất "tách file theo môn học" đã cân nhắc: chỉ giảm tranh chấp chéo môn,
  KHÔNG giảm được ca 50 HS cùng lớp cùng môn nộp cùng lúc, lại tốn công sửa nhiều
  nơi đọc → đã loại.
