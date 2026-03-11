# CẤU TRÚC DỮ LIỆU CÂU HỎI

## Tổng Quan

Hệ thống quản lý câu hỏi sử dụng cấu trúc JSON với phân cấp 3 tầng:

1. **Topic (Chủ đề)** - Phạm vi rộng (Chương, Phần)
2. **Lesson (Bài học)** = **Đơn vị kiến thức (ĐVKT)** - Phạm vi cụ thể
3. **Questions (Câu hỏi)** - Các câu hỏi cụ thể thuộc về ĐVKT

## Cấu Trúc JSON

```json
[
  {
    "topic": "Chương 1: Phương trình bậc hai",
    "lesson": "Bài 1: Giải phương trình bậc hai",
    "questions": [
      {
        "question": "Nội dung câu hỏi (hỗ trợ LaTeX: $x^2$)",
        "options": ["Đáp án A", "Đáp án B", "Đáp án C", "Đáp án D"],
        "correct": 1,
        "type": "single",
        "level": "NB"
      }
    ]
  }
]
```

## Giải Thích Các Trường

### Cấp 1: Topic & Lesson

| Trường | Mô tả | Ví dụ |
|--------|-------|-------|
| `topic` | Chủ đề - phạm vi rộng (Chương, Phần) | "Chương 1: Phương trình bậc hai" |
| `lesson` | **Đơn vị kiến thức (ĐVKT)** - phạm vi cụ thể | "Bài 1: Giải phương trình bậc hai" |
| `questions` | Mảng các câu hỏi thuộc về ĐVKT này | `[...]` |

**Quan trọng:**
- `lesson` chính là **Đơn vị kiến thức (ĐVKT)**
- Mỗi câu hỏi được gắn với một ĐVKT cụ thể thông qua `lesson`
- Khi xuất đề, có thể lọc theo ĐVKT (lesson)

### Cấp 2: Question Fields

| Trường | Kiểu | Bắt buộc | Mô tả |
|--------|------|----------|-------|
| `question` | string | ✅ | Nội dung câu hỏi (hỗ trợ LaTeX: `$x^2$`) |
| `options` | array[4] | ✅ | Mảng 4 đáp án (index 0-3) |
| `correct` | int hoặc array | ✅ | Đáp án đúng: số (single) hoặc mảng (multiple) |
| `type` | string | ✅ | `"single"` hoặc `"multiple"` |
| `level` | string | ✅ | `"NB"`, `"TH"`, `"VD"`, `"VDC"` |

## Ví Dụ Chi Tiết

### Single Choice (1 đáp án đúng)

```json
{
  "topic": "Chương 1: Phương trình bậc hai",
  "lesson": "Bài 1: Giải phương trình bậc hai",
  "questions": [
    {
      "question": "Phương trình bậc hai có dạng tổng quát: $ax^2 + bx + c = 0$. Điều kiện của a là gì?",
      "options": [
        "$a = 0$",
        "$a \\neq 0$",
        "$a > 0$",
        "$a < 0$"
      ],
      "correct": 1,
      "type": "single",
      "level": "NB"
    }
  ]
}
```

### Multiple Choice (nhiều đáp án đúng)

```json
{
  "topic": "Chương 2: Hóa học",
  "lesson": "Bài 1: Kim loại kiềm",
  "questions": [
    {
      "question": "Các kim loại nào sau đây thuộc nhóm kim loại kiềm?",
      "options": [
        "Natri (Na)",
        "Kali (K)",
        "Canxi (Ca)",
        "Sắt (Fe)"
      ],
      "correct": [0, 1],
      "type": "multiple",
      "level": "TH"
    }
  ]
}
```

## Công Thức Toán Học (LaTeX)

### Inline Formula (trong câu)
- Cú pháp: `$công_thức$`
- Ví dụ: `$x^2 + 2x + 1$`, `$\\frac{a}{b}$`, `$\\sqrt{x}$`

### Display Formula (độc lập)
- Cú pháp: `$$công_thức$$`
- Ví dụ: `$$\\lim_{x \\to 0} \\frac{\\sin x}{x}$$`

### Các công thức thường dùng

| Ký hiệu | LaTeX | Kết quả |
|---------|-------|---------|
| Phân số | `$\\frac{a}{b}$` | a/b |
| Căn bậc 2 | `$\\sqrt{x}$` | √x |
| Mũ | `$x^2$` | x² |
| Chỉ số dưới | `$H_2O$` | H₂O |
| Tích phân | `$\\int_0^1 x dx$` | ∫₀¹ x dx |
| Tổng | `$\\sum_{i=1}^{n} x_i$` | Σᵢ₌₁ⁿ xᵢ |
| Delta | `$\\Delta$` | Δ |
| Góc | `$\\angle ABC$` | ∠ABC |

## Mức Độ Câu Hỏi

| Mã | Tên đầy đủ | Mô tả |
|----|-----------|-------|
| `NB` | Nhận biết | Câu hỏi về kiến thức cơ bản, định nghĩa |
| `TH` | Thông hiểu | Câu hỏi yêu cầu hiểu rõ khái niệm |
| `VD` | Vận dụng | Câu hỏi áp dụng kiến thức vào bài tập |
| `VDC` | Vận dụng cao | Câu hỏi phức tạp, tổng hợp nhiều kiến thức |

## Import từ Word

Khi import từ Word, format như sau:

```
Chủ đề: Chương 1: Phương trình bậc hai
Bài học: Bài 1: Giải phương trình bậc hai (ĐVKT)

Câu 1: [Mức độ: NB] [Loại: single]
Phương trình bậc hai có dạng:

A) $ax + b = 0$
B) $ax^2 + bx + c = 0$ *
C) $ax^3 = 0$
D) Không có dạng chuẩn
```

**Lưu ý:**
- Đánh dấu `*` sau đáp án đúng
- `[Mức độ:]` và `[Loại:]` không bắt buộc (mặc định: NB, single)

## File Lưu Trữ

Câu hỏi được lưu theo cấu trúc:

```
questions/
  ├── khoi6/
  │   ├── hk1/
  │   │   ├── subject_1.json  (Tin học)
  │   │   ├── subject_2.json  (Toán)
  │   │   └── ...
  │   └── hk2/
  │       └── ...
  ├── khoi7/
  ├── khoi8/
  └── khoi9/
```

**Đường dẫn:** `questions/{grade}/{semester}/subject_{subject_id}.json`

## Xem File Mẫu

- **JSON mẫu:** [MAU_JSON_CAU_HOI.json](MAU_JSON_CAU_HOI.json)
- **Word mẫu:** Tải từ trang Quản Lý Câu Hỏi → nút "📥 Tải Mẫu Word"
- **Format chuẩn:** [FORMAT_CAU_HOI_WORD.html](FORMAT_CAU_HOI_WORD.html)
- **Hướng dẫn:** [HUONG_DAN_THEM_TU_WORD.md](HUONG_DAN_THEM_TU_WORD.md)

---

**Tóm tắt quan trọng:**
- ✅ `lesson` = Đơn vị kiến thức (ĐVKT)
- ✅ Mỗi câu hỏi gắn với 1 ĐVKT cụ thể
- ✅ Hỗ trợ LaTeX cho công thức toán, lý, hóa
- ✅ 4 mức độ: NB, TH, VD, VDC
- ✅ 2 loại: single (1 đáp án) hoặc multiple (nhiều đáp án)
