# 📝 HỆ THỐNG TẠO ĐỀ KIỂM TRA TỰ ĐỘNG

> Tạo đề kiểm tra tự động từ Ma trận đặc tả với câu hỏi ngẫu nhiên từ ngân hàng câu hỏi

## ✨ Tính năng

- ✅ Tạo đề tự động từ ma trận
- ✅ Random câu hỏi từ ngân hàng
- ✅ Tạo nhiều đề (A, B, C, D)
- ✅ Thay câu hỏi bất kỳ
- ✅ Xuất Word đề + đáp án

## 🚀 Bắt đầu

**Xem ngay:** [QUICK_START.md](./QUICK_START.md) (3 bước đơn giản)

**Chi tiết:** [EXAM_GENERATION_COMPLETE.md](./EXAM_GENERATION_COMPLETE.md)

## 📂 Cấu trúc

```
teacher/
├── migrate_questions_structure.php    # Migration script
├── api/
│   ├── generate_exam.php              # API tạo đề
│   └── export_exam.php                # API xuất Word
├── questions/                         # Ngân hàng câu hỏi
│   └── {grade}/{semester}/{subject}.json
├── exams/generated/                   # Đề đã tạo
├── matrix_builder.php                 # UI (đã update)
└── QUICK_START.md                     # Hướng dẫn nhanh
```

## 🎯 Workflow

```
Ma trận đặc tả 
    ↓
[📝 Tạo đề Kiểm tra]
    ↓
Random chọn câu từ ngân hàng
    ↓
Preview + thay câu
    ↓
[📄 Xuất Word]
    ↓
Nhận file đề + đáp án
```

## 📱 Screenshots

**Button "Tạo đề Kiểm tra":**
```
[Kết quả Ma trận]  [Sửa / Quay lại] [📄 Xuất Word] [📝 Tạo đề Kiểm tra]
```

**Modal tạo đề:**
```
╔══════════════════════════════════╗
║ 📝 Tạo đề Kiểm tra từ Ma trận    ║
╠══════════════════════════════════╣
║ Tiêu đề: [Kiểm tra giữa kỳ I]   ║
║ Số đề:   [2 đề (A, B) ▼]        ║
║ ☑ Random thứ tự câu hỏi          ║
║ ☑ Random thứ tự đáp án           ║
║                                  ║
║     [✨ Tạo đề ngay]             ║
╚══════════════════════════════════╝
```

**Preview đề:**
```
╔══════════════════════════════════╗
║ Đề đã tạo: [Đề A ▼]             ║
╠══════════════════════════════════╣
║ Câu 1 [TNKQ] [NB] [0.5đ] [🔄]  ║
║ Thông tin là gì?                 ║
║ A. Đáp án 1                      ║
║ B. Đáp án 2                      ║
║                                  ║
║ Tổng: 20 câu | 10 điểm          ║
║                                  ║
║   [📄 Xuất đề Word]             ║
╚══════════════════════════════════╝
```

## 📝 Cấu trúc câu hỏi

```json
{
  "unit": "Bài 1: Thông tin và dữ liệu",
  "topic": "Chủ đề 1: Thông tin và dữ liệu",
  "questions": [{
    "id": "Q_TNKQ_NB_xxxxx",
    "question": "Thông tin là gì?",
    "options": ["A", "B", "C", "D"],
    "correct": 0,
    "question_type": "TNKQ",    // TNKQ/DS/TL
    "level": "NB",               // NB/TH/VD
    "points": 0.5
  }]
}
```

## 🔧 Cài đặt

**Yêu cầu:**
- PHP 7.4+
- Composer
- PHPWord: `composer require phpoffice/phpword`

**Migration (1 lần đầu):**
```bash
cd c:\xampp\htdocs\cvd2\teacher
php migrate_questions_structure.php
```

## 📚 Documents

- **[QUICK_START.md](./QUICK_START.md)** - Hướng dẫn nhanh 3 bước
- **[EXAM_GENERATION_COMPLETE.md](./EXAM_GENERATION_COMPLETE.md)** - Tài liệu đầy đủ
- **[EXAM_GENERATION_ROADMAP.md](./EXAM_GENERATION_ROADMAP.md)** - Roadmap chi tiết

## 🎓 Support

**Lỗi thường gặp:**
- Không tìm thấy câu hỏi → Thêm vào `questions/{grade}/{hk}/{subject}.json`
- PHPWord error → Cài: `composer require phpoffice/phpword`
- Unauthorized → Đăng nhập bằng tài khoản giáo viên

## ✅ Status

- [x] Migration script ✅
- [x] API generate_exam ✅
- [x] API export_exam ✅
- [x] UI integration ✅
- [x] Documentation ✅
- [x] **READY TO USE** 🚀

---

**🎉 Hệ thống sẵn sàng! Bắt đầu tạo đề ngay.**
