# Hướng Dẫn Triển Khai Chức Năng Học Kì Cho Question Bank

## 📋 Tóm Tắt Thay Đổi

Hệ thống đã được nâng cấp để hỗ trợ phân chia câu hỏi theo **Học kì 1** và **Học kì 2**, giúp quản lý câu hỏi dễ dàng và có tổ chức hơn.

## 🗂️ Cấu Trúc Thư Mục Mới

### Trước khi nâng cấp:
```
questions/
  ├── khoi6/
  │   ├── subject_1.json
  │   └── subject_2.json
  ├── khoi7/
  ├── khoi8/
  └── khoi9/
```

### Sau khi nâng cấp:
```
questions/
  ├── khoi6/
  │   ├── hk1/
  │   │   ├── subject_1.json
  │   │   └── subject_2.json
  │   └── hk2/
  │       ├── subject_1.json
  │       └── subject_2.json
  ├── khoi7/
  │   ├── hk1/
  │   └── hk2/
  ├── khoi8/
  │   ├── hk1/
  │   └── hk2/
  └── khoi9/
      ├── hk1/
      └── hk2/
```

## 🔄 Các File Đã Được Cập Nhật

### 1. **question_bank.php** (File chính)
- ✅ Thêm biến `$selectedSemester` để lưu trữ học kì được chọn
- ✅ Thêm mảng `$semesters` và `$semesterLabels`
- ✅ Thêm validation cho học kì
- ✅ Cập nhật đường dẫn đọc file: `questions/{khoi}/{hk}/subject_{id}.json`
- ✅ Thêm dropdown chọn học kì vào form filter
- ✅ Cập nhật URL export để bao gồm semester

### 2. **question_bank_handlers.php** (Xử lý CRUD)
- ✅ **Export handler**: Thêm semester vào tên file export
- ✅ **Add question handler**: Lưu vào thư mục `{khoi}/{semester}/`
- ✅ **Delete question handler**: Đọc từ thư mục có semester
- ✅ **Delete all handler**: Xóa file trong thư mục có semester
- ✅ **Edit question handler**: Đọc/ghi từ thư mục có semester
- ✅ **Import JSON handler**: Thêm validation và lưu vào semester được chọn
- ✅ **Import Excel handler**: Thêm validation và lưu vào semester được chọn

### 3. **migrate_add_semester.php** (Script Migration - MỚI)
- ✅ Tạo thư mục `hk1/` và `hk2/` cho mỗi khối
- ✅ Di chuyển tất cả file hiện tại vào `hk1/` (mặc định)
- ✅ Tạo file rỗng trong `hk2/` cho các môn học
- ✅ Backup file gốc với extension `.backup`
- ✅ Tạo log chi tiết về quá trình migration

## 📝 Hướng Dẫn Sử Dụng

### Bước 1: Chạy Migration Script
```
1. Mở trình duyệt
2. Truy cập: http://localhost/cvd2/teacher/migrate_add_semester.php
3. Xem kết quả migration và kiểm tra log
4. Sau khi xác nhận migration thành công, có thể xóa các file .backup
```

### Bước 2: Cập Nhật Form Import (Nếu có)
Các form import cần được cập nhật để bao gồm dropdown chọn học kì:

```html
<select name="import_semester" class="form-select" required>
    <option value="">-- Chọn học kì --</option>
    <option value="hk1">Học kì 1</option>
    <option value="hk2">Học kì 2</option>
</select>
```

### Bước 3: Sử Dụng Trang Question Bank
1. Chọn **Khối** (Khối 6, 7, 8, 9)
2. Chọn **Môn học** (Tin học, Toán, ...)
3. Chọn **Học kì** (Học kì 1 hoặc Học kì 2) - **MỚI!**
4. Thêm, sửa, xóa câu hỏi như bình thường

## 🎯 Lưu Ý Quan Trọng

### ⚠️ Trước Khi Migration
1. **Backup toàn bộ thư mục `questions/`** để đề phòng
2. Đảm bảo không có user nào đang sử dụng hệ thống
3. Kiểm tra quyền ghi file trên server (chmod 755)

### ✅ Sau Khi Migration
1. Kiểm tra các file đã được di chuyển đúng vào `hk1/`
2. Kiểm tra file `.backup` đã được tạo
3. Test chức năng thêm/sửa/xóa câu hỏi
4. Test chức năng import/export

## 🔧 Troubleshooting

### Lỗi: "Học kì không hợp lệ"
- **Nguyên nhân**: Chưa chọn học kì
- **Giải pháp**: Chọn Học kì 1 hoặc Học kì 2 từ dropdown

### Lỗi: "File câu hỏi không tồn tại"
- **Nguyên nhân**: Chưa chạy migration hoặc file chưa được tạo
- **Giải pháp**: Chạy script migration hoặc thêm câu hỏi mới để tạo file

### Migration không hoạt động
- **Kiểm tra**: Quyền ghi file (chmod -R 755 questions/)
- **Kiểm tra**: Đường dẫn thư mục đúng
- **Kiểm tra**: PHP có quyền tạo thư mục

## 📊 Thống Kê Thay Đổi

| File | Số dòng thay đổi | Loại thay đổi |
|------|------------------|---------------|
| question_bank.php | ~40 dòng | Thêm filter, cập nhật logic đọc file |
| question_bank_handlers.php | ~60 dòng | Cập nhật tất cả handlers |
| migrate_add_semester.php | ~150 dòng | File mới - Migration script |

## 🚀 Tính Năng Mới

1. ✅ **Phân chia theo học kì**: Câu hỏi được tổ chức theo Học kì 1 và Học kì 2
2. ✅ **Quản lý dễ dàng**: Dễ dàng xem và chỉnh sửa câu hỏi của từng học kì
3. ✅ **Export có tổ chức**: Tên file export bao gồm cả học kì
4. ✅ **Migration an toàn**: Dữ liệu cũ được backup tự động
5. ✅ **Backward compatible**: Không ảnh hưởng đến dữ liệu hiện có

## 📞 Hỗ Trợ

Nếu gặp vấn đề trong quá trình triển khai, vui lòng kiểm tra:
- Log migration tại màn hình sau khi chạy migration
- File backup (.backup) để rollback nếu cần
- Console log của browser (F12) để debug JavaScript

---

**Phiên bản**: 2.0  
**Ngày cập nhật**: <?php echo date('d/m/Y'); ?>  
**Tác giả**: GitHub Copilot
