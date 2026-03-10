# ✅ HỆ THỐNG TẠO ĐỀ KIỂM TRA - HOÀN TẤT

**Ngày hoàn thành:** 2026-03-07  
**Trạng thái:** READY TO USE ✨

---

## 📋 TÓM TẮT CHỨC NĂNG

Hệ thống cho phép giáo viên:
1. ✅ **Tạo đề kiểm tra tự động** từ ma trận đã xây dựng
2. ✅ **Chọn câu hỏi ngẫu nhiên** từ ngân hàng câu hỏi theo yêu cầu ma trận
3. ✅ **Tạo nhiều đề** (A, B, C, D) với câu hỏi và đáp án khác nhau
4. ✅ **Xem preview** và **thay thế câu hỏi** bất kỳ
5. ✅ **Xuất Word** đề thi + đáp án chuẩn format

---

## 🎯 WORKFLOW SỬ DỤNG

```
1. Giáo viên tạo Ma trận đặc tả
   ↓
2. Click "📝 Tạo đề Kiểm tra"
   ↓
3. Nhập tiêu đề, chọn số đề (A, B, C...)
   ↓
4. Hệ thống tự động chọn câu hỏi phù hợp
   ↓
5. Preview đề → Có thể thay câu hỏi bất kỳ
   ↓
6. Click "📄 Xuất Word" → Nhận file đề + đáp án
```

---

## 📂 CÁC FILE ĐÃ TẠO/SỬA

### 1. Migration Script
**File:** `teacher/migrate_questions_structure.php`
- Chuẩn hóa cấu trúc câu hỏi trong JSON
- Thêm fields: `id`, `question_type`, `points`, `difficulty`, `tags`, `usage_count`, `last_used`
- Chuyển `lesson` → `unit`
- Backup tự động trước khi migrate
- Log chi tiết quá trình

**Status:** ✅ Đã test thành công trên subject_1.json

### 2. API Generate Exam
**File:** `teacher/api/generate_exam.php`
- **Endpoint 1:** `POST ?action=generate`
  * Input: matrix_data, exam_title, grade, subject, semester, options
  * Output: Exam với multiple variants
  * Lưu vào `exams/generated/{exam_id}.json`
  
- **Endpoint 2:** `POST ?action=replace_question`
  * Thay thế 1 câu hỏi trong đề
  * Filter theo constraints (topic, unit, level, type)
  * Random chọn câu mới không trùng
  
- **Endpoint 3:** `GET ?action=get_exam&id=xxx`
  * Lấy lại đề đã tạo

**Algorithm:**
```php
1. Parse ma trận → Lấy requirements (TNKQ: 2 NB, DS: 1 TH, TL: 1 VD...)
2. Load all questions từ JSON
3. Filter questions theo: topic + unit + level + question_type
4. Random select có weight (ưu tiên câu ít dùng)
5. Shuffle options nếu cần
6. Tạo multiple variants
7. Lưu exam data
```

**Status:** ✅ Hoàn tất

### 3. API Export Exam
**File:** `teacher/api/export_exam.php`
- **Endpoint:** `GET ?action=export_word&exam_id=xxx&variant=A&include_answers=1`
- Sử dụng **PHPWord** để tạo file .docx
- Format chuẩn:
  * Header: Tên trường, tổ, đề chính thức
  * Title: Tiêu đề đề thi, môn, lớp, thời gian
  * Phần A: TRẮC NGHIỆM KHÁCH QUAN
  * Phần B: ĐÚNG/SAI
  * Phần C: TỰ LUẬN
  * Footer: "--- HẾT ---"
  * **Page 2**: ĐÁP ÁN VÀ HƯỚNG DẪN CHẤM

**Status:** ✅ Hoàn tất

### 4. UI Updates
**File:** `teacher/matrix_builder.php`

**Thêm:**
- Button "📝 Tạo đề Kiểm tra" trong result-area (line ~1924)
- Modal `#exam-generator-modal` với:
  * Form config: tiêu đề, số đề, options
  * Preview section: hiển thị danh sách câu hỏi
  * Button thay câu hỏi cho từng câu
  * Button xuất Word
  * Variant selector (xem đề A, B, C...)
  
**JavaScript:**
- Event handler cho button "Tạo đề"
- Gọi API generate_exam
- Render preview câu hỏi
- Replace question functionality
- Export to Word

**Status:** ✅ Hoàn tất

### 5. Cấu trúc câu hỏi MỚI
**File:** `teacher/questions/{grade}/{semester}/{subject}.json`

**Trước:**
```json
{
  "topic": "Chủ đề 1",
  "lesson": "Bài 1",
  "questions": [{
    "question": "...",
    "options": [],
    "correct": 0,
    "type": "single",
    "level": "NB"
  }]
}
```

**SAU (Migrated):**
```json
{
  "topic": "Chủ đề 1",
  "unit": "Bài 1",          // ← Đổi từ "lesson"
  "questions": [{
    "id": "Q_TNKQ_NB_1a8e58",      // [NEW] Unique ID
    "question": "...",
    "options": [],
    "correct": 0,
    "answer_type": "single",        // ← Type cũ
    "question_type": "TNKQ",        // [NEW] TNKQ/DS/TL
    "level": "NB",
    "points": 0.5,                  // [NEW]
    "difficulty": 1,                // [NEW] 1-5
    "tags": ["thông tin"],          // [NEW]
    "explanation": "",              // [NEW]
    "usage_count": 0,               // [NEW]
    "last_used": null               // [NEW]
  }]
}
```

**Status:** ✅ Migrated cho khoi6/hk1/subject_1.json

---

## 🗂️ EXAM DATA STRUCTURE

**File lưu:** `teacher/exams/generated/EXAM_20260307_161959_xxxx.json`

```json
{
  "id": "EXAM_20260307_161959_a3f2",
  "title": "Kiểm tra giữa kỳ I",
  "grade": "khoi6",
  "subject": "subject_1",
  "semester": "hk1",
  "teacher_id": "xxx",
  "created_at": "2026-03-07 16:19:59",
  "variants": [
    {
      "variant": "A",
      "questions": [
        {
          "number": 1,
          "id": "Q_TNKQ_NB_1a8e58",
          "question": "Thông tin là gì?",
          "options": ["A", "B", "C", "D"],
          "correct": 0,
          "correct_text": "A",
          "question_type": "TNKQ",
          "level": "NB",
          "required_points": 0.5,
          "_topic": "Chủ đề 1: Thông tin và dữ liệu",
          "_unit": "Bài 1: Thông tin và dữ liệu"
        }
      ],
      "total_questions": 20,
      "total_points": 10.0,
      "distribution": {
        "TNKQ": 10,
        "DS": 6,
        "TL": 4
      }
    },
    {
      "variant": "B",
      "questions": [...]
    }
  ],
  "requirements": {
    "TNKQ": [...],
    "DS": [...],
    "TL": [...]
  },
  "options": {
    "create_variants": 2,
    "randomize_questions": true,
    "randomize_answers": true
  }
}
```

---

## 💡 TÍNH NĂNG NỔI BẬT

### 1. Random Có Trọng Số
```php
// Ưu tiên câu ít dùng
$weight = 1 / ($question['usage_count'] + 1);
```
→ Đảm bảo câu hỏi được phân bổ đều, không lặp lại quá nhiều

### 2. Validation Đầy Đủ
- ✅ Kiểm tra đủ câu hỏi cho ma trận
- ✅ Session authentication
- ✅ JSON parsing error handling
- ✅ File existence checks

### 3. Backup Tự Động
Migration script tự động backup thư mục `questions/` trước khi thay đổi:
```
questions_backup_20260307_161959/
```

### 4. Logging Chi Tiết
Migration log ghi lại:
- Số file processed
- Số câu hỏi migrated
- Errors nếu có

File: `migration_log_20260307_161959.txt`

---

## 🚀 CÁCH SỬ DỤNG

### Bước 1: Migration câu hỏi (Chỉ 1 lần đầu)
```bash
cd c:\xampp\htdocs\cvd2\teacher

# Dry-run để xem preview
php migrate_questions_structure.php --dry-run

# Chạy thật cho tất cả file
php migrate_questions_structure.php

# Hoặc chỉ migrate 1 file:
php migrate_questions_structure.php --file="questions/khoi6/hk1/subject_1.json"
```

### Bước 2: Tạo đề từ UI
1. Truy cập `matrix_builder.php`
2. Tạo ma trận như bình thường
3. Click "📝 Tạo đề Kiểm tra"
4. Nhập thông tin:
   - Tiêu đề: "Kiểm tra giữa kỳ I"
   - Số đề: 2 (A, B)
   - ✓ Random thứ tự câu hỏi
   - ✓ Random thứ tự đáp án
5. Click "✨ Tạo đề ngay"
6. Xem preview → Thay câu nếu muốn
7. Click "📄 Xuất đề Word"

### Bước 3: Nhận file Word
- File tự động download: `De_Thi_A.docx`
- Bao gồm:
  * Page 1: Đề thi
  * Page 2: Đáp án + hướng dẫn chấm

---

## 🔧 YÊU CẦU HỆ THỐNG

### PHP Extensions:
- ✅ `json` (built-in)
- ✅ `zip` (for PHPWord)
- ✅ `xml` (for PHPWord)

### Dependencies:
- ✅ **PHPWord**: Installed via Composer
  ```bash
  composer require phpoffice/phpword
  ```

### File Permissions:
- ✅ `teacher/questions/` → Readable
- ✅ `teacher/exams/generated/` → Writable (đã tạo)
- ✅ `teacher/questions_backup_*/` → Writable

---

## 📊 THỐNG KÊ MIGRATION

**File đã migrate:** `khoi6/hk1/subject_1.json`
- **Topics:** 1
- **Questions:** 13 câu
- **Backup:** `questions_backup_20260307_161959/`
- **Log:** `migration_log_20260307_161959.txt`
- **Status:** ✅ SUCCESS

**Phân loại câu hỏi:**
- TNKQ (Trắc nghiệm): 13 câu
- DS (Đúng/Sai): 0 câu
- TL (Tự luận): 0 câu

---

## 🎓 HƯỚNG DẪN MỞ RỘNG

### Thêm câu hỏi mới vào ngân hàng:
1. Mở file JSON: `questions/{grade}/{semester}/{subject}.json`
2. Thêm câu hỏi theo cấu trúc MỚI (đã migrate)
3. Đảm bảo có đủ fields:
   ```json
   {
     "id": "Q_TNKQ_NB_xxxxx",
     "question": "...",
     "options": [...],
     "correct": 0,
     "answer_type": "single",
     "question_type": "TNKQ",
     "level": "NB",
     "points": 0.5,
     "difficulty": 1,
     "tags": [],
     "explanation": "",
     "usage_count": 0,
     "last_used": null
   }
   ```

### Tạo câu hỏi Đúng/Sai (DS):
```json
{
  "id": "Q_DS_TH_xxxxx",
  "question": "Xét tính đúng/sai của các nhận định:",
  "options": [
    "Thông tin giúp ra quyết định",
    "Dữ liệu là thông tin đã xử lý",
    "Máy tính xử lý dữ liệu",
    "Con người cần thông tin"
  ],
  "correct": [0, 2, 3],        // Array of correct indices
  "answer_type": "multiple",
  "question_type": "DS",
  "level": "TH",
  "points": 1.0,
  "difficulty": 2,
  "tags": ["thông tin", "dữ liệu"],
  "explanation": "a-Đ, b-S, c-Đ, d-Đ",
  "usage_count": 0,
  "last_used": null
}
```

### Tạo câu hỏi Tự luận (TL):
```json
{
  "id": "Q_TL_VD_xxxxx",
  "question": "Em hãy phân tích sự khác biệt giữa dữ liệu và thông tin?",
  "options": null,              // TL không có options
  "correct": null,
  "answer_type": "essay",
  "question_type": "TL",
  "level": "VD",
  "points": 2.0,
  "difficulty": 3,
  "tags": ["dữ liệu", "thông tin", "phân tích"],
  "explanation": "Dữ liệu: nguyên liệu thô, chưa xử lý (0.5đ). Thông tin: dữ liệu đã xử lý, có ý nghĩa (0.5đ). Ví dụ cụ thể (0.5đ). So sánh rõ ràng (0.5đ).",
  "usage_count": 0,
  "last_used": null
}
```

---

## 🐛 TROUBLESHOOTING

### Lỗi: "Question file not found"
**Nguyên nhân:** File JSON không tồn tại
**Giải pháp:** Kiểm tra path `teacher/questions/{grade}/{semester}/{subject}.json`

### Lỗi: "No alternative questions found"
**Nguyên nhân:** Không đủ câu hỏi để thay thế
**Giải pháp:** Thêm câu hỏi vào ngân hàng với cùng topic/unit/level/type

### Lỗi: "PHPWord not found"
**Nguyên nhân:** Chưa cài PHPWord
**Giải pháp:**
```bash
cd c:\xampp\htdocs\cvd2
composer require phpoffice/phpword
```

### Lỗi: "Unauthorized"
**Nguyên nhân:** Chưa đăng nhập hoặc không phải giáo viên
**Giải pháp:** Đăng nhập với tài khoản teacher

---

## 📈 KẾ HOẠCH TƯƠNG LAI (Optional)

### Phase 2:
- [ ] Thêm exam_library.php (quản lý đề đã tạo)
- [ ] Import câu hỏi từ Word/Excel
- [ ] AI suggest câu hỏi dựa trên độ khó
- [ ] Thống kê usage_count tự động

### Phase 3:
- [ ] Tích hợp chấm tự động
- [ ] Phân tích kết quả thi
- [ ] Đề xuất cải thiện ma trận
- [ ] Mobile app

---

## ✅ CHECKLIST HOÀN THÀNH

- [x] Script migration cấu trúc câu hỏi
- [x] Test migration thành công
- [x] API generate_exam.php
- [x] API export_exam.php
- [x] UI button "Tạo đề Kiểm tra"
- [x] Modal config đề thi
- [x] Preview & thay câu hỏi
- [x] Export Word với format chuẩn
- [x] Tạo thư mục exams/generated
- [x] Documentation đầy đủ
- [x] Test end-to-end

---

**🎉 HỆ THỐNG SẴN SÀNG SỬ DỤNG!**

Giáo viên có thể bắt đầu tạo đề kiểm tra tự động ngay bây giờ. 🚀
