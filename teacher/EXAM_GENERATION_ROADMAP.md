# 🎯 HỆ THỐNG TẠO ĐỀ KIỂM TRA TỰ ĐỘNG

## 📊 PHÂN TÍCH CẤU TRÚC

### Cấu trúc Câu hỏi HIỆN TẠI:
```json
{
  "topic": "Chủ đề 1: Thông tin và dữ liệu",
  "lesson": "Bài 1: Thông tin và dữ liệu",
  "questions": [
    {
      "question": "Thông tin là gì?",
      "options": ["A", "B", "C", "D"],
      "correct": 0,
      "type": "single",      // single/multiple
      "level": "NB"          // NB/TH/VD
    }
  ]
}
```

### Cấu trúc MỚI đề xuất:
```json
{
  "topic": "Chủ đề 1: Thông tin và dữ liệu",
  "unit": "Bài 1: Thông tin và dữ liệu",  // Đổi từ "lesson" → "unit"
  "questions": [
    {
      "id": "Q001_NB_TNKQ",          // [NEW] Unique ID
      "question": "Thông tin là gì?",
      "options": ["A", "B", "C", "D"],
      "correct": 0,
      "answer_type": "single",        // single/multiple/true-false/essay
      "level": "NB",                  // NB/TH/VD
      "question_type": "TNKQ",        // [NEW] TNKQ/DS/TL
      "points": 0.5,                  // [NEW] Điểm câu hỏi
      "difficulty": 1,                // [NEW] Độ khó 1-5
      "tags": ["thông tin", "khái niệm"],  // [NEW] Tags tìm kiếm
      "explanation": "Thông tin là...",    // [NEW] Giải thích
      "usage_count": 0,               // [NEW] Số lần sử dụng
      "last_used": null               // [NEW] Lần dùng cuối
    }
  ]
}
```

---

## 🚀 ROADMAP TRIỂN KHAI

### ✅ PHASE 1: CHUẨN HÓA DỮ LIỆU (Ngày 1)
**File:** `migrate_questions_structure.php`

1. ✅ Scan tất cả file JSON trong `teacher/questions/`
2. ✅ Phân loại question_type tự động:
   - `type: "single"` → `question_type: "TNKQ"`
   - `type: "multiple"` với 4 true/false → `question_type: "DS"`
   - Câu văn tự luận → `question_type: "TL"`
3. ✅ Thêm các field mới với giá trị mặc định
4. ✅ Backup file cũ trước khi sửa
5. ✅ Tạo log migration

**Output:** Questions chuẩn hóa sẵn sàng sử dụng

---

### ⚙️ PHASE 2: API TẠO ĐỀ (Ngày 1-2)
**File:** `teacher/api/generate_exam.php`

#### Endpoint 1: `POST /api/generate_exam.php?action=generate`
**Input:**
```json
{
  "matrix_data": { /* Ma trận đã tạo */ },
  "exam_title": "Kiểm tra giữa kỳ",
  "grade": "khoi6",
  "subject": "subject_1",
  "semester": "hk1",
  "options": {
    "randomize": true,
    "allow_duplicate": false,
    "create_variants": 2  // Tạo 2 đề A, B
  }
}
```

**Output:**
```json
{
  "success": true,
  "exam_id": "EXAM_20260307_001",
  "variants": [
    {
      "variant": "A",
      "questions": [
        {
          "number": 1,
          "type": "TNKQ",
          "level": "NB",
          "content": "...",
          "options": ["A", "B", "C", "D"],
          "correct_answer": "A",
          "points": 0.5,
          "topic": "Chủ đề 1",
          "unit": "Bài 1"
        }
      ],
      "total_questions": 20,
      "total_points": 10.0,
      "distribution": {
        "TNKQ": 10, "DS": 6, "TL": 4
      }
    }
  ]
}
```

#### Endpoint 2: `POST /api/generate_exam.php?action=replace_question`
**Input:**
```json
{
  "exam_id": "EXAM_20260307_001",
  "variant": "A",
  "question_number": 5,
  "constraints": {
    "topic": "Chủ đề 1",
    "unit": "Bài 1",
    "level": "NB",
    "type": "TNKQ"
  }
}
```

**Algorithm:**
1. Parse ma trận → Lấy requirements từng ô
2. Query questions matching (topic, unit, level, type)
3. Random select đúng số lượng
4. Shuffle options
5. Tạo variants (đảo câu, đảo đáp án)
6. Save exam to `teacher/exams/generated/`

---

### 🎨 PHASE 3: UI TẠO ĐỀ (Ngày 2-3)
**File:** `teacher/exam_generator.php`

#### 3.1 Button trong Ma trận
```html
<button id="generate-exam-btn" class="btn btn-success">
  📝 Tạo đề Kiểm tra
</button>
```

#### 3.2 Modal Tùy chọn
```html
<div class="modal" id="exam-generator-modal">
  <h4>Tạo đề Kiểm tra từ Ma trận</h4>
  
  <label>Tiêu đề đề thi:</label>
  <input type="text" id="exam-title" value="Kiểm tra giữa kỳ I">
  
  <label>Số đề (A, B, C...):</label>
  <select id="variant-count">
    <option value="1">1 đề</option>
    <option value="2" selected>2 đề (A, B)</option>
    <option value="3">3 đề (A, B, C)</option>
    <option value="4">4 đề (A, B, C, D)</option>
  </select>
  
  <label>
    <input type="checkbox" checked> Random thứ tự câu hỏi
  </label>
  <label>
    <input type="checkbox" checked> Random thứ tự đáp án
  </label>
  
  <button id="btn-generate-exam">Tạo đề</button>
</div>
```

#### 3.3 Preview & Edit
```html
<div id="exam-preview">
  <h4>ĐỀ A - Kiểm tra giữa kỳ I</h4>
  
  <!-- Danh sách câu hỏi -->
  <div class="question-item">
    <span class="q-number">Câu 1</span>
    <span class="q-type badge">TNKQ</span>
    <span class="q-level badge">Biết</span>
    <span class="q-points">0.5đ</span>
    <p>Thông tin là gì?</p>
    <ol type="A">
      <li>Là dữ liệu đã được xử lý...</li>
      <li>...</li>
    </ol>
    <button class="btn-replace-question" data-qnum="1">
      🔄 Thay câu khác
    </button>
  </div>
  
  <!-- Thống kê -->
  <div class="exam-stats">
    <p>Tổng: 20 câu | 10.0 điểm</p>
    <p>TNKQ: 10c (5đ) | Đúng/Sai: 6ý (1.5đ) | Tự luận: 4c (3.5đ)</p>
  </div>
  
  <button id="btn-export-word">📄 Xuất Word</button>
  <button id="btn-export-pdf">📄 Xuất PDF</button>
</div>
```

---

### 📄 PHASE 4: EXPORT ĐỀ THI (Ngày 3-4)
**File:** `teacher/api/export_exam.php`

#### Format Word:
```
TRƯỜNG THCS ABC
TỔ: TIN HỌC
---

ĐỀ KIỂM TRA GIỮA KỲ I
MÔN: TIN HỌC - LỚP 6
Thời gian: 45 phút

ĐỀ A
========================================

A. TRẮC NGHIỆM (5 điểm)
Khoanh tròn chữ cái trước câu trả lời đúng:

Câu 1. (0.5 điểm) Thông tin là gì?
A. Là dữ liệu đã được xử lý và mang ý nghĩa
B. Là các con số ngẫu nhiên
C. Là ký hiệu không có ý nghĩa
D. Là tập hợp các chữ cái

Câu 2. (0.5 điểm) ...

B. CÂU HỎI ĐÚNG/SAI (1.5 điểm)
Chọn Đ (đúng) hoặc S (sai):

Câu 11. (0.25 điểm × 4 = 1.0 điểm)
a) [ ] Dữ liệu là thông tin đã xử lý
b) [ ] Máy tính xử lý dữ liệu thành thông tin
c) [ ] Thông tin luôn đúng 100%
d) [ ] Con người cần thông tin để ra quyết định

C. TỰ LUẬN (3.5 điểm)
Trả lời ngắn gọn:

Câu 15. (1.0 điểm) Em hãy cho ví dụ về dữ liệu và thông tin trong thực tế?

Câu 16. (1.25 điểm) ...

========================================
--- HẾT ---
```

#### ĐÁP ÁN & HƯỚNG DẪN CHẤM:
```
ĐÁP ÁN ĐỀ A

A. TRẮC NGHIỆM:
1-A  2-C  3-D  4-B  5-A  
6-C  7-D  8-B  9-A  10-C

B. ĐÚNG/SAI:
11. a-S, b-Đ, c-S, d-Đ
12. a-Đ, b-Đ, c-S, d-Đ

C. TỰ LUẬN - HƯỚNG DẪN CHẤM:
Câu 15: (1.0 điểm)
- Ví dụ dữ liệu: tên, tuổi, điểm số (0.5đ)
- Ví dụ thông tin: bảng xếp hạng, báo cáo (0.5đ)
```

---

## 🔍 PHASE 5: QUẢN LÝ ĐỀ ĐÃ TẠO (Ngày 4-5)
**File:** `teacher/exam_library.php`

### Features:
- ✅ Danh sách đề đã tạo
- ✅ Tìm kiếm, lọc theo môn/khối/học kỳ
- ✅ Xem lại, chỉnh sửa
- ✅ Tạo biến thể mới từ đề cũ
- ✅ Xóa đề
- ✅ Thống kê câu hỏi đã dùng

---

## 📂 CẤU TRÚC THƯ MỤC

```
teacher/
├── api/
│   ├── generate_exam.php          [NEW] API tạo đề
│   └── export_exam.php            [NEW] API xuất đề
├── exams/
│   └── generated/                 [NEW] Lưu đề đã tạo
│       ├── EXAM_20260307_001.json
│       └── EXAM_20260307_002.json
├── questions/                     [CÓ SẴN]
│   ├── khoi6/hk1/subject_1.json
│   └── ...
├── exam_generator.php             [NEW] UI tạo đề
├── exam_library.php               [NEW] Quản lý đề
└── migrate_questions_structure.php [NEW] Script migration

data/
└── exam_templates/                [NEW] Templates Word
    ├── default_template.docx
    └── simple_template.docx
```

---

## 🎯 CHECKLIST TRIỂN KHAI

### Ngày 1:
- [ ] Tạo script migration cấu trúc
- [ ] Test migration trên 1 file mẫu
- [ ] Backup toàn bộ questions/
- [ ] Chạy migration cho tất cả file

### Ngày 2:
- [ ] Implement API generate_exam.php
- [ ] Implement query & matching algorithm
- [ ] Test tạo đề với ma trận mẫu
- [ ] Implement replace_question API

### Ngày 3:
- [ ] Thêm button "Tạo đề" vào matrix_builder.php
- [ ] Tạo modal tùy chọn
- [ ] Implement preview & edit UI
- [ ] Kết nối frontend → backend

### Ngày 4:
- [ ] Implement export Word
- [ ] Implement export PDF
- [ ] Format đề + đáp án chuẩn
- [ ] Test export với nhiều format

### Ngày 5:
- [ ] Tạo exam_library.php
- [ ] Implement quản lý đề
- [ ] Thống kê & báo cáo
- [ ] Tổng kiểm tra end-to-end

---

## 🔐 BẢO MẬT & KIỂM SOÁT

1. **Quyền truy cập**: Chỉ giáo viên đã đăng nhập
2. **Audit log**: Ghi lại ai tạo đề nào, khi nào
3. **Versioning**: Lưu lịch sử chỉnh sửa đề
4. **Backup**: Tự động backup khi migration

---

## 📈 KẾ HOẠCH MỞ RỘNG

### Giai đoạn 2 (Tuần 2):
- AI suggest câu hỏi dựa trên độ khó
- Phân tích thống kê câu hỏi hay sai
- Import câu hỏi từ Word/Excel
- Tạo bank câu hỏi chung toàn trường

### Giai đoạn 3 (Tuần 3-4):
- Tích hợp chấm tự động
- Phân tích kết quả thi
- Đề xuất cải thiện ma trận
- Mobile app tạo đề

---

## 💡 GHI CHÚ KỸ THUẬT

### Thuật toán Random có trọng số:
```php
// Ưu tiên câu ít dùng
weight = 1 / (usage_count + 1)
probability = weight / sum(weights)
```

### Cache để tăng tốc:
```php
// Cache questions per subject/grade
cache_key = "questions_{$grade}_{$subject}_{$semester}"
```

### Validation:
- Đủ câu hỏi cho ma trận không?
- Phân bổ điểm có hợp lệ không?
- Format câu hỏi có chuẩn không?

---

✅ **Status**: READY TO IMPLEMENT
🎯 **Timeline**: 5 ngày
👨‍💻 **Owner**: Copilot + User
📅 **Start Date**: 2026-03-07
