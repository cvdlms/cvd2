# Cải thiện UI trang tạo đề kiểm tra

Ngày thực hiện: 2026-06-13

## Phạm vi

- Trang: `teacher/exam_creation.php`
- Stylesheet mới: `teacher/assets/exam_creation.css`
- Không thay đổi cấu trúc JSON, API POST hoặc quy tắc nghiệp vụ tạo đề hiện có.

## Đã thực hiện

- Tái cấu trúc trang thành workspace gồm:
  - Phạm vi khối lớp và môn học.
  - Số liệu nhanh về ngân hàng câu hỏi, tổng đề, đề đã duyệt và bản nháp.
  - Danh sách đề.
  - Luồng tạo đề thủ công.
  - Luồng tạo đề tự động.
- Bổ sung tìm kiếm và lọc danh sách đề theo:
  - Tên đề.
  - Loại kiểm tra/luyện tập.
  - Trạng thái đã duyệt/chưa duyệt.
- Cải thiện luồng tạo thủ công:
  - Tách thông tin chung và ngân hàng câu hỏi.
  - Hiển thị tiến độ số câu đã chọn.
  - Tóm tắt câu hỏi đã chọn theo chủ đề.
  - Giữ giới hạn tối đa 20 câu.
- Cải thiện luồng tạo tự động:
  - Giải thích rõ ba mức NB, TH, VD.
  - Xem trước số câu tương ứng với từng tỷ lệ.
  - Chặn gửi form khi tổng tỷ lệ khác 100%.
- Cải thiện modal xem đề thành dạng tài liệu rà soát.
- Cải thiện modal chỉnh sửa thành ba khu vực rõ ràng.
- Bổ sung responsive cho màn hình desktop, tablet và điện thoại.
- Loại bỏ phần xem đề lặp bên dưới bảng vì chức năng này đã có trong modal.

## Kết quả kiểm tra

- `php -l teacher/exam_creation.php`: thành công.
- Render qua Apache với phiên giáo viên và dữ liệu `khoi7`, môn Tin học: HTTP 200.
- JavaScript sau khi PHP render: cú pháp hợp lệ.
- Không có ID HTML bị trùng.
- Các ID/form cũ phục vụ JavaScript và backend vẫn được giữ lại.
- `git diff --check`: không có lỗi whitespace.

## Trạng thái

Hoàn thành phần triển khai và kiểm tra kỹ thuật. Chưa chụp ảnh kiểm tra trực quan do trình duyệt tích hợp không khả dụng trong phiên làm việc này.
