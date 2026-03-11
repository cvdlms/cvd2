# Question Bank - Ngân hàng câu hỏi

## 📁 Cấu trúc thư mục

```
questions/
├── QUESTION_FORMAT.md          # Chi tiết về định dạng JSON
├── SAMPLE_NEW_FORMAT.json      # File mẫu với format mới
├── migrate_questions.php       # Script chuyển đổi format cũ → mới
├── validate_questions.php      # Script kiểm tra tính hợp lệ
├── README.md                   # File này
├── khoi6/
│   ├── hk1/
│   │   ├── subject_1.json     # Tin học Khối 6 HK1
│   │   └── subject_2.json
│   └── hk2/
├── khoi7/
├── khoi8/
└── khoi9/
```

## 📋 Quick Start

### 1. Tạo file câu hỏi mới

**Sử dụng file mẫu:**
```bash
cp SAMPLE_NEW_FORMAT.json khoi8/hk1/subject_1.json
```

**Chỉnh sửa:**
- Mở file JSON
- Cập nhật `topic_name` khớp với Bản đặc tả kỹ thuật
- Cập nhật `unit_name` khớp với Bản đặc tả
- Thêm/sửa câu hỏi

### 2. Kiểm tra tính hợp lệ

```bash
php validate_questions.php khoi8/hk1/subject_1.json
```

**Kết quả:**
- ✅ Validation PASSED - File hợp lệ
- ⚠️ Warnings - Có cảnh báo nhưng vẫn dùng được
- ❌ Errors - Có lỗi, cần sửa

### 3. Chuyển đổi format cũ sang mới

```bash
php migrate_questions.php khoi8/hk1/subject_1.json khoi8/hk1/subject_1_new.json
```

**Review kết quả:**
```bash
cat khoi8/hk1/subject_1_new.json
```

**Backup và replace:**
```bash
mv khoi8/hk1/subject_1.json khoi8/hk1/subject_1.backup.json
mv khoi8/hk1/subject_1_new.json khoi8/hk1/subject_1.json
```

## 📖 Định dạng JSON

### Format mới (RECOMMENDED - v5.0+)

```json
[
  {
    "topic_name": "Chủ đề F. Giải quyết vấn đề với sự trợ giúp của máy tính",
    "unit_name": "Lập trình trực quan",
    "questions": [
      {
        "question": "Khái niệm nào sau đây là hằng?",
        "options": ["Pi = 3.14", "x = 5", "y = x + 1", "z"],
        "correct": 0,
        "type": "single",
        "level": "NB"
      }
    ]
  }
]
```

**Chi tiết:** Xem [QUESTION_FORMAT.md](QUESTION_FORMAT.md)

## 🔗 Mapping với Bản đặc tả kỹ thuật

### ⚠️ QUAN TRỌNG

`topic_name` và `unit_name` phải **khớp chính xác** với Bản đặc tả kỹ thuật để hệ thống tạo đề thi đúng.

### Kiểm tra Bản đặc tả

**Vào trang:**
1. Teacher Dashboard → Matrix Builder
2. Chọn môn học và khối
3. Load Bản đặc tả kỹ thuật

**Xem JSON:**
- File: `data/knowledge_assessments.json`
- Cấu trúc:
```json
{
  "items": [
    {
      "content": "Chủ đề F. ...",
      "units": [
        {
          "unit_name": "Lập trình trực quan",
          "nhan_biet": "...",
          "thong_hieu": "...",
          "van_dung": "..."
        }
      ]
    }
  ]
}
```

### Mapping

| Bản đặc tả | Question JSON |
|------------|---------------|
| `items[].content` | `topic_name` |
| `items[].units[].unit_name` | `unit_name` |

**Ví dụ:**

Bản đặc tả có:
```json
{
  "content": "Chủ đề F. Giải quyết vấn đề với sự trợ giúp của máy tính",
  "units": [{"unit_name": "Lập trình trực quan"}]
}
```

→ Question JSON phải có:
```json
{
  "topic_name": "Chủ đề F. Giải quyết vấn đề với sự trợ giúp của máy tính",
  "unit_name": "Lập trình trực quan"
}
```

## 🎯 Loại câu hỏi

### 1. Trắc nghiệm (TNKQ)
```json
{
  "type": "single",
  "question": "...",
  "options": ["A", "B", "C", "D"],
  "correct": 0,
  "level": "NB"
}
```

### 2. Đúng/Sai (DS)
```json
{
  "type": "true_false",
  "question": "...",
  "options": ["Đúng", "Sai"],
  "correct": 0,
  "level": "TH"
}
```

### 3. Tự luận (TL)
```json
{
  "type": "essay",
  "question": "...",
  "level": "VD",
  "points": 2.0
}
```

## 📊 Mức độ nhận thức

| Mã | Tên | Bloom's Taxonomy |
|----|-----|------------------|
| NB | Nhận biết | Remember |
| TH | Thông hiểu | Understand |
| VD | Vận dụng | Apply |
| VDC | Vận dụng cao | Analyze/Evaluate/Create |

## ✅ Quy trình kiểm tra

### Trước khi commit

1. **Validate JSON:**
   ```bash
   php validate_questions.php khoi8/hk1/subject_1.json
   ```

2. **Kiểm tra mapping:**
   - So sánh `topic_name` với Bản đặc tả
   - So sánh `unit_name` với Bản đặc tả

3. **Test tạo đề:**
   - Vào Matrix Builder
   - Tạo ma trận với các đơn vị kiến thức
   - Bấm "Tạo đề ngay"
   - Kiểm tra câu hỏi có được chọn đúng không

### Nếu không match

**Triệu chứng:**
- API trả về `variants[].questions = []` (rỗng)
- Không có câu hỏi nào được chọn

**Nguyên nhân:**
- `topic_name` không khớp với `content` trong Bản đặc tả
- `unit_name` không khớp với `unit_name` trong Bản đặc tả

**Giải pháp:**
1. Mở `data/knowledge_assessments.json`
2. Tìm môn học và khối tương ứng
3. Copy chính xác `content` và `unit_name`
4. Update vào question JSON

## 🔧 Troubleshooting

### Lỗi: "Question file not found"
```
Question file not found: khoi8/hk1/subject_1.json
```

**Giải pháp:**
- Kiểm tra file có tồn tại không
- Đúng cấu trúc thư mục: `khoi{grade}/hk{semester}/subject_{id}.json`

### Lỗi: "JSON parse error"
```
JSON parse error: Syntax error
```

**Giải pháp:**
- Chạy validate: `php validate_questions.php <file>`
- Kiểm tra dấu `,` thừa sau phần tử cuối
- Kiểm tra `"` đóng/mở

### Câu hỏi không được chọn (variants rỗng)

**Debug steps:**
1. Mở Console (F12)
2. Xem `requirements` trong API response
3. So sánh `topic`, `unit` với JSON
4. Nếu không khớp → update JSON

**Fallback:**
- API tự động fallback về filter chỉ theo `type` + `level`
- Nhưng sẽ chọn ngẫu nhiên từ toàn bộ câu hỏi (không đúng unit)

## 📚 Tài liệu liên quan

- [QUESTION_FORMAT.md](QUESTION_FORMAT.md) - Định dạng chi tiết
- [SAMPLE_NEW_FORMAT.json](SAMPLE_NEW_FORMAT.json) - File mẫu
- Matrix Builder - `/teacher/matrix_builder.php`
- Exam Generator API - `/teacher/api/generate_exam.php`

## 🆕 Version History

### v5.0 (2026-03-10)
- ✅ Thêm format mới với `topic_name` và `unit_name`
- ✅ Mapping chính xác với Bản đặc tả kỹ thuật
- ✅ Backwards compatible với format cũ
- ✅ Thêm migration script
- ✅ Thêm validation script

### v4.0 (2026-03-07)
- Format cũ với `topic` và `lesson`
- Không mapping với Bản đặc tả

---

**Lưu ý:** Từ v5.0 trở đi, khuyến nghị sử dụng format mới để tận dụng tính năng mapping với Bản đặc tả kỹ thuật.
