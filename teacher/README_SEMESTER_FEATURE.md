# Tích Hợp Chức Năng Học Kì - Question Bank System

## 🎯 Tổng Quan

Hệ thống quản lý câu hỏi đã được nâng cấp để hỗ trợ phân chia theo **Học kì 1** và **Học kì 2**, giúp giáo viên quản lý câu hỏi một cách có hệ thống và khoa học hơn.

---

## 📦 Các File Đã Được Cập Nhật

### 1. **question_bank.php** - File Chính
**Các thay đổi:**
- ✅ Thêm biến `$selectedSemester` để lưu học kì được chọn
- ✅ Thêm mảng `$semesters = ['hk1', 'hk2']` và `$semesterLabels`
- ✅ Thêm validation cho học kì
- ✅ Cập nhật đường dẫn đọc file: `questions/{khoi}/{semester}/subject_{id}.json`
- ✅ Thêm dropdown "Chọn Học Kì" vào form filter (layout 3 cột: Khối, Môn học, Học kì)
- ✅ Cập nhật URL export để bao gồm semester
- ✅ Cập nhật form import JSON để có dropdown học kì

**Code mẫu:**
```php
$selectedSemester = $_GET['semester'] ?? '';
$questionsFile = __DIR__ . "/questions/{$selectedGrade}/{$selectedSemester}/subject_{$selectedSubjectId}.json";
```

---

### 2. **question_bank_handlers.php** - Xử Lý CRUD
**Các handler đã được cập nhật:**

#### a. Export Handler
```php
// Tên file export có thêm semester
header('Content-Disposition: attachment; filename="questions_' . $selectedGrade . '_' . $selectedSemester . '_subject_' . $selectedSubjectId . '.json"');
```

#### b. Add Question Handler
```php
// Lưu vào thư mục có semester
$questionsDir = __DIR__ . "/questions/{$selectedGrade}/{$selectedSemester}";
```

#### c. Delete Question Handler
```php
// Đọc từ thư mục có semester
$questionsFile = __DIR__ . "/questions/{$selectedGrade}/{$selectedSemester}/subject_{$selectedSubjectId}.json";
```

#### d. Edit Question Handler
```php
// Đọc/ghi từ thư mục có semester
$questionsFile = __DIR__ . "/questions/{$selectedGrade}/{$selectedSemester}/subject_{$selectedSubjectId}.json";
```

#### e. Import JSON Handler
```php
// Validate semester và lưu vào đúng thư mục
$semester = $_POST['import_semester'] ?? '';
if (!in_array($semester, ['hk1', 'hk2'])) {
    $importError = 'Học kì không hợp lệ.';
}
$questionsDir = __DIR__ . '/questions/' . $grade . '/' . $semester . '/';
```

#### f. Import Excel Handler
```php
// Validate semester và lưu vào đúng thư mục
$semester = $_POST['excel_import_semester'] ?? '';
if (!in_array($semester, ['hk1', 'hk2'])) {
    $importError = 'Học kì không hợp lệ.';
}
```

---

### 3. **question_bank_modals.php** - Modal Import Excel
**Các thay đổi:**
- ✅ Thêm dropdown "Chọn Học Kì" vào form import Excel
- ✅ Thay đổi layout từ 2 cột (col-md-6) sang 3 cột (col-md-4) để chứa: Khối, Môn học, Học kì

**Code mẫu:**
```html
<div class="col-md-4">
    <label for="excel_import_semester" class="form-label">Chọn Học Kì</label>
    <select id="excel_import_semester" name="excel_import_semester" class="form-select" required>
        <option value="">-- Chọn học kì --</option>
        <option value="hk1">Học kì 1</option>
        <option value="hk2">Học kì 2</option>
    </select>
</div>
```

---

### 4. **migrate_add_semester.php** - Script Migration (MỚI)
**Chức năng:**
- ✅ Tạo cấu trúc thư mục mới: `{khoi}/hk1/` và `{khoi}/hk2/`
- ✅ Di chuyển tất cả file hiện tại vào `hk1/` (mặc định)
- ✅ Tạo file JSON rỗng trong `hk2/` cho các môn học
- ✅ Backup file gốc với extension `.backup`
- ✅ Hiển thị log chi tiết về quá trình migration

**Cách chạy:**
```
http://localhost/cvd2/teacher/migrate_add_semester.php
```

---

## 🔧 Hướng Dẫn Triển Khai

### Bước 1: Backup Dữ Liệu
```bash
# Backup toàn bộ thư mục questions
cp -r questions/ questions_backup/
```

### Bước 2: Chạy Migration Script
1. Truy cập: `http://localhost/cvd2/teacher/migrate_add_semester.php`
2. Kiểm tra log migration
3. Xác nhận các file đã được di chuyển đúng

### Bước 3: Kiểm Tra Kết Quả
Sau khi migration, cấu trúc thư mục sẽ như sau:
```
questions/
  ├── khoi6/
  │   ├── hk1/
  │   │   ├── subject_1.json
  │   │   └── subject_2.json
  │   ├── hk2/
  │   │   ├── subject_1.json (file rỗng [])
  │   │   └── subject_2.json (file rỗng [])
  │   ├── subject_1.json.backup (backup gốc)
  │   └── subject_2.json.backup (backup gốc)
```

### Bước 4: Test Chức Năng
1. Truy cập trang question_bank
2. Chọn Khối → Môn học → **Học kì** (mới)
3. Test các chức năng:
   - ✅ Xem danh sách câu hỏi
   - ✅ Thêm câu hỏi mới
   - ✅ Sửa câu hỏi
   - ✅ Xóa câu hỏi
   - ✅ Import từ JSON
   - ✅ Import từ Excel
   - ✅ Export câu hỏi

---

## 📊 Cấu Trúc Dữ Liệu

### Cấu trúc file JSON không thay đổi:
```json
[
  {
    "topic": "Chủ đề 1: Thông tin và dữ liệu",
    "lesson": "Bài 1: Thông tin và dữ liệu",
    "questions": [
      {
        "question": "Thông tin là gì?",
        "options": ["Đáp án A", "Đáp án B", "Đáp án C", "Đáp án D"],
        "correct": 0,
        "type": "single",
        "level": "NB"
      }
    ]
  }
]
```

**Chỉ thay đổi vị trí lưu trữ file:**
- Trước: `questions/khoi6/subject_1.json`
- Sau: `questions/khoi6/hk1/subject_1.json` hoặc `questions/khoi6/hk2/subject_1.json`

---

## 🎨 Giao Diện Người Dùng

### Form Filter - Layout mới:
```
┌─────────────┬─────────────┬─────────────┐
│  Chọn Khối  │ Chọn Môn    │ Chọn Học Kì │
│  (Khối 6-9) │ (Tin học..) │ (HK1/HK2)   │
└─────────────┴─────────────┴─────────────┘
```

### Form Import JSON - Layout mới:
```
┌──────────┬──────────┬──────────┬──────────┐
│Chọn Khối │ Chọn Môn │ Học Kì   │ File JSON│
│(col-md-3)│(col-md-3)│(col-md-2)│(col-md-4)│
└──────────┴──────────┴──────────┴──────────┘
```

### Form Import Excel - Layout mới:
```
┌──────────┬──────────┬──────────┐
│Chọn Khối │ Chọn Môn │ Học Kì   │
│(col-md-4)│(col-md-4)│(col-md-4)│
└──────────┴──────────┴──────────┘
┌───────────────────────────────┐
│     Chọn File Excel (.xlsx)   │
│         (col-12)              │
└───────────────────────────────┘
```

---

## ⚠️ Lưu Ý Quan Trọng

### Điều kiện hiển thị câu hỏi:
Câu hỏi chỉ hiển thị khi đã chọn đủ **3 yếu tố**:
```php
if ($selectedGrade && $selectedSubjectId && $selectedSemester) {
    // Hiển thị danh sách câu hỏi
}
```

### Validation:
- ✅ Khối: phải thuộc `['khoi6', 'khoi7', 'khoi8', 'khoi9']`
- ✅ Môn học: phải nằm trong danh sách môn được phân công
- ✅ Học kì: phải là `'hk1'` hoặc `'hk2'`

### Export file:
- Tên file mới: `questions_{khoi}_{semester}_subject_{id}.json`
- Ví dụ: `questions_khoi6_hk1_subject_1.json`

---

## 🐛 Troubleshooting

### Lỗi: "Học kì không hợp lệ"
**Nguyên nhân:** Chưa chọn học kì hoặc giá trị không hợp lệ  
**Giải pháp:** Chọn "Học kì 1" hoặc "Học kì 2" từ dropdown

### Lỗi: "File câu hỏi không tồn tại"
**Nguyên nhân:** Chưa chạy migration hoặc file chưa được tạo  
**Giải pháp:** 
1. Chạy script migration
2. Hoặc thêm câu hỏi mới để tự động tạo file

### Migration không hoạt động
**Kiểm tra:**
- Quyền ghi file: `chmod -R 755 questions/`
- PHP có quyền tạo thư mục
- Đường dẫn thư mục đúng

---

## 📈 Lợi Ích

### 1. Tổ Chức Tốt Hơn
- Câu hỏi được phân chia rõ ràng theo học kì
- Dễ dàng tìm kiếm và quản lý

### 2. Tránh Nhầm Lẫn
- Giáo viên không bị nhầm lẫn giữa câu hỏi của các học kì khác nhau
- Mỗi học kì có bộ câu hỏi riêng biệt

### 3. Dễ Bảo Trì
- Cấu trúc thư mục rõ ràng
- Dễ backup và restore theo từng học kì

### 4. Mở Rộng Trong Tương Lai
- Có thể thêm học kì 3 (hè) nếu cần: `hk3`
- Có thể thêm filter theo năm học

---

## 🔄 Rollback (Nếu Cần)

Nếu cần quay lại cấu trúc cũ:

1. **Xóa thư mục hk1 và hk2:**
```bash
rm -rf questions/*/hk1
rm -rf questions/*/hk2
```

2. **Restore file backup:**
```bash
cd questions/khoi6
mv subject_1.json.backup subject_1.json
mv subject_2.json.backup subject_2.json
# Lặp lại cho các khối khác
```

---

## 📞 Hỗ Trợ

Nếu có vấn đề, kiểm tra:
1. Log migration tại `migrate_add_semester.php`
2. Browser console (F12) để debug JavaScript
3. File backup (`.backup`) để rollback

---

**Phiên bản:** 2.0  
**Ngày cập nhật:** <?php echo date('d/m/Y H:i:s'); ?>  
**Trạng thái:** ✅ Đã hoàn thành và sẵn sàng sử dụng
