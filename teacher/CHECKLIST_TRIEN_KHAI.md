# ✅ Checklist Triển Khai Chức Năng Học Kì

## 📋 Trước Khi Bắt Đầu

- [ ] Backup toàn bộ thư mục `questions/` vào `questions_backup/`
- [ ] Backup database (nếu có)
- [ ] Thông báo cho các giáo viên tạm ngưng sử dụng hệ thống
- [ ] Kiểm tra quyền ghi file: `chmod -R 755 questions/`

---

## 🔄 Chạy Migration

- [ ] Truy cập: `http://localhost/cvd2/teacher/migrate_add_semester.php`
- [ ] Đọc kỹ kết quả migration
- [ ] Kiểm tra số file thành công vs thất bại
- [ ] Lưu lại log migration (copy/paste vào file text)
- [ ] Xác nhận các file `.backup` đã được tạo

---

## 🧪 Test Các Chức Năng Cơ Bản

### Test 1: Xem Danh Sách Câu Hỏi
- [ ] Truy cập trang question_bank
- [ ] Chọn Khối (ví dụ: Khối 6)
- [ ] Chọn Môn học (ví dụ: Tin học)
- [ ] Chọn Học kì 1
- [ ] Xác nhận: Danh sách câu hỏi hiển thị đúng (các câu hỏi từ file cũ)
- [ ] Chuyển sang Học kì 2
- [ ] Xác nhận: Danh sách trống hoặc "Không có câu hỏi nào"

### Test 2: Thêm Câu Hỏi Mới
- [ ] Chọn Khối, Môn học, Học kì 2
- [ ] Click "➕ Thêm Câu Hỏi"
- [ ] Điền thông tin câu hỏi
- [ ] Submit form
- [ ] Xác nhận: Câu hỏi mới xuất hiện trong danh sách
- [ ] Kiểm tra file: `questions/khoi{X}/hk2/subject_{Y}.json` đã được tạo

### Test 3: Sửa Câu Hỏi
- [ ] Click vào một câu hỏi để mở modal
- [ ] Click "✏️ Sửa"
- [ ] Thay đổi nội dung câu hỏi
- [ ] Submit form
- [ ] Xác nhận: Câu hỏi đã được cập nhật

### Test 4: Xóa Câu Hỏi
- [ ] Click "🗑️ Xóa" trên một câu hỏi
- [ ] Xác nhận xóa
- [ ] Xác nhận: Câu hỏi đã bị xóa khỏi danh sách

### Test 5: Export Câu Hỏi
- [ ] Chọn Khối, Môn học, Học kì 1
- [ ] Click "📥 Xuất Câu Hỏi"
- [ ] Xác nhận: File JSON được tải về
- [ ] Kiểm tra tên file: `questions_khoi{X}_hk1_subject_{Y}.json`
- [ ] Mở file JSON và kiểm tra nội dung

### Test 6: Import từ JSON
- [ ] Click vào phần "📤 Nhập Câu Hỏi Từ File JSON"
- [ ] Chọn Khối, Môn học, **Học kì 2**
- [ ] Chọn file JSON (có thể dùng file vừa export ở Test 5)
- [ ] Click "📤 Nhập Câu Hỏi"
- [ ] Xác nhận: Thông báo "Câu hỏi đã được nhập thành công"
- [ ] Refresh trang và chọn lại Khối, Môn học, Học kì 2
- [ ] Xác nhận: Các câu hỏi đã được nhập vào

### Test 7: Import từ Excel
- [ ] Click "Thêm từ Excel"
- [ ] Chọn Khối, Môn học, **Học kì 1**
- [ ] Tải mẫu Excel: Click "📥 Tải mẫu Excel"
- [ ] Điền thông tin vào file Excel
- [ ] Upload file Excel
- [ ] Submit form
- [ ] Xác nhận: Thông báo "Câu hỏi đã được nhập từ Excel thành công"

### Test 8: Xóa Tất Cả
- [ ] Chọn Khối, Môn học, Học kì 2 (chứa câu hỏi test)
- [ ] Click "🗑️ Xóa Tất Cả"
- [ ] Xác nhận xóa
- [ ] Xác nhận: Tất cả câu hỏi đã bị xóa
- [ ] Kiểm tra file: `questions/khoi{X}/hk2/subject_{Y}.json` = `[]`

---

## 🔍 Kiểm Tra Cấu Trúc Thư Mục

- [ ] Kiểm tra thư mục `questions/khoi6/hk1/` tồn tại
- [ ] Kiểm tra thư mục `questions/khoi6/hk2/` tồn tại
- [ ] Kiểm tra file `questions/khoi6/hk1/subject_1.json` có dữ liệu
- [ ] Kiểm tra file `questions/khoi6/hk2/subject_1.json` tồn tại (có thể rỗng)
- [ ] Kiểm tra file backup `questions/khoi6/subject_1.json.backup` tồn tại
- [ ] Lặp lại cho các khối 7, 8, 9

---

## 🎯 Kiểm Tra Các Edge Cases

### Edge Case 1: Chọn thiếu thông tin
- [ ] Chọn chỉ Khối (không chọn Môn học và Học kì)
- [ ] Xác nhận: Hiển thị "Vui lòng chọn khối và môn học để xem câu hỏi"
- [ ] Chọn Khối + Môn học (không chọn Học kì)
- [ ] Xác nhận: Hiển thị "Vui lòng chọn khối và môn học để xem câu hỏi"

### Edge Case 2: Import sai định dạng
- [ ] Thử import file JSON không đúng định dạng
- [ ] Xác nhận: Hiển thị thông báo lỗi "Định dạng câu hỏi không hợp lệ"

### Edge Case 3: Import không chọn Học kì
- [ ] Thử submit form import mà không chọn Học kì
- [ ] Xác nhận: Form validation ngăn không cho submit (required field)

---

## 📱 Kiểm Tra Responsive (Tùy Chọn)

- [ ] Mở trang trên mobile (hoặc responsive mode trong browser)
- [ ] Kiểm tra dropdown "Chọn Học Kì" hiển thị đúng
- [ ] Kiểm tra form import hiển thị đúng trên mobile
- [ ] Kiểm tra bảng danh sách câu hỏi có scroll ngang

---

## 🧹 Dọn Dẹp Sau Khi Test

- [ ] Xóa các câu hỏi test đã tạo
- [ ] Xóa các file `.backup` nếu migration thành công (tùy chọn)
- [ ] Thông báo cho giáo viên có thể sử dụng lại hệ thống
- [ ] Cập nhật tài liệu hướng dẫn cho giáo viên

---

## 📊 Báo Cáo Kết Quả

### Thống Kê Migration:
- Số file được migrate thành công: _____ / _____
- Số file thất bại: _____ / _____
- Thời gian migration: _____ giây/phút

### Kết Quả Test:
- Tổng số test cases: 8
- Số test cases passed: _____ / 8
- Số test cases failed: _____ / 8

### Vấn Đề Gặp Phải (Nếu Có):
```
1. [Mô tả vấn đề]
   - Giải pháp: [...]
   
2. [Mô tả vấn đề]
   - Giải pháp: [...]
```

---

## ✅ Xác Nhận Cuối Cùng

- [ ] Tất cả test cases đều PASS
- [ ] Không có lỗi nào xuất hiện trong console
- [ ] Dữ liệu cũ vẫn còn nguyên vẹn trong Học kì 1
- [ ] Migration script đã chạy thành công
- [ ] Các giáo viên đã được hướng dẫn sử dụng tính năng mới

---

**Người thực hiện:** _________________________  
**Ngày thực hiện:** _________________________  
**Chữ ký:** _________________________

---

## 🚨 Trong Trường Hợp Có Lỗi

Nếu gặp lỗi nghiêm trọng, thực hiện rollback:

1. **Dừng hệ thống ngay lập tức**
2. **Restore từ backup:**
   ```bash
   rm -rf questions/
   cp -r questions_backup/ questions/
   ```
3. **Báo cáo lỗi chi tiết**
4. **Không xóa file backup cho đến khi vấn đề được giải quyết**

---

**Lưu ý:** Checklist này cần được thực hiện đầy đủ trước khi đưa hệ thống vào sử dụng chính thức.
