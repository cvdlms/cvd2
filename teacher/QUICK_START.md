# 🚀 QUICK START - TẠO ĐỀ KIỂM TRA TỰ ĐỘNG

## ⚡ 3 BƯỚC ĐƠN GIẢN

### 1️⃣ Migration câu hỏi (Chỉ làm 1 lần đầu)

```bash
cd c:\xampp\htdocs\cvd2\teacher
php migrate_questions_structure.php
```

✅ Xong! Câu hỏi đã được chuẩn hóa.

---

### 2️⃣ Tạo ma trận (như bình thường)

1. Vào `matrix_builder.php`
2. Chọn môn, khối, học kỳ
3. Thêm chủ đề, đơn vị, nhập số câu
4. Click **"Tạo ma trận"**

---

### 3️⃣ Tạo đề thi

1. Click **"📝 Tạo đề Kiểm tra"**
2. Nhập tiêu đề: *"Kiểm tra giữa kỳ I"*
3. Chọn số đề: **2 đề (A, B)**
4. Click **"✨ Tạo đề ngay"**
5. Xem preview → Thay câu nếu muốn (click **🔄 Thay**)
6. Click **"📄 Xuất đề Word"**

**➡️ Xong! Nhận file `De_Thi_A.docx` với đề + đáp án**

---

## 📝 MẪU CẤU TRÚC CÂU HỎI

### TNKQ (Trắc nghiệm):
```json
{
  "id": "Q_TNKQ_NB_xxxxx",
  "question": "Thông tin là gì?",
  "options": ["Đáp án A", "Đáp án B", "Đáp án C", "Đáp án D"],
  "correct": 0,
  "answer_type": "single",
  "question_type": "TNKQ",
  "level": "NB",
  "points": 0.5,
  "difficulty": 1,
  "tags": ["thông tin"],
  "explanation": "",
  "usage_count": 0,
  "last_used": null
}
```

### DS (Đúng/Sai):
```json
{
  "id": "Q_DS_TH_xxxxx",
  "question": "Xét tính đúng/sai:",
  "options": ["Nhận định a", "Nhận định b", "Nhận định c", "Nhận định d"],
  "correct": [0, 2, 3],
  "answer_type": "multiple",
  "question_type": "DS",
  "level": "TH",
  "points": 1.0,
  "difficulty": 2,
  "tags": [],
  "explanation": "a-Đ, b-S, c-Đ, d-Đ",
  "usage_count": 0,
  "last_used": null
}
```

### TL (Tự luận):
```json
{
  "id": "Q_TL_VD_xxxxx",
  "question": "Em hãy phân tích...",
  "options": null,
  "correct": null,
  "answer_type": "essay",
  "question_type": "TL",
  "level": "VD",
  "points": 2.0,
  "difficulty": 3,
  "tags": ["phân tích"],
  "explanation": "Hướng dẫn chấm chi tiết...",
  "usage_count": 0,
  "last_used": null
}
```

---

## 🎯 LEVEL

- **NB** = Biết (Nhận biết) → difficulty: 1
- **TH** = Hiểu (Thông hiểu) → difficulty: 2
- **VD** = Vận dụng → difficulty: 3
- **VDC** = Vận dụng cao → difficulty: 4

---

## ⚠️ LƯU Ý

1. **File câu hỏi** phải ở: `teacher/questions/{khoi}/{hk}/{mon}.json`
2. **Migration** backup tự động vào `questions_backup_YYYYMMDD_HHMMSS/`
3. **Đề đã tạo** lưu tại: `teacher/exams/generated/`
4. **PHPWord** cần có: `composer require phpoffice/phpword`
5. **Đăng nhập** bằng tài khoản giáo viên

---

## 🆘 HỖ TRỢ

**Lỗi không tìm thấy câu hỏi?**
→ Thêm câu vào `questions/{khoi}/{hk}/{mon}.json`

**Lỗi PHPWord?**
→ Chạy: `cd c:\xampp\htdocs\cvd2; composer require phpoffice/phpword`

**Muốn xem log migration?**
→ Mở file `migration_log_YYYYMMDD_HHMMSS.txt`

---

## 📚 DOCUMENTS

- **Chi tiết:** [EXAM_GENERATION_COMPLETE.md](./EXAM_GENERATION_COMPLETE.md)
- **Roadmap:** [EXAM_GENERATION_ROADMAP.md](./EXAM_GENERATION_ROADMAP.md)

---

**✨ Chúc tạo đề thành công!**
