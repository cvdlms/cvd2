# HƯỚNG DẪN NHANH - IMPORT CÂU HỎI TỪ WORD

## 3 Loại Câu Hỏi Chính

### 1️⃣ Trắc Nghiệm (TNKQ)

**Single choice (1 đáp án):**
```
Câu 1: [Mức độ: NB] [Loại: single]
Phương trình bậc hai có dạng:

A) ax + b = 0
B) ax^2 + bx + c = 0 *
C) ax^3 + bx^2 + cx = 0
D) Dạng khác

Đáp án đúng: B
---
```

**Multiple choice (nhiều đáp án):**
```
Câu 2: [Mức độ: TH] [Loại: multiple]
Thiết bị nào là thiết bị ra?

A) Màn hình *
B) Bàn phím
C) Loa *
D) Máy in *

Đáp án đúng: A, C, D
---
```

### 2️⃣ Đúng/Sai (DS)

**Đúng/Sai đơn giản:**
```
Câu 3: [Mức độ: NB] [Loại: true_false]
Excel là phần mềm bảng tính.

A) Đúng *
B) Sai

Đáp án đúng: A
---
```

**Đúng/Sai nhiều ý (4 phát biểu):**
```
Câu 4: [Mức độ: TH] [Loại: true_false_multiple]
Giáo viên nhập điểm vào Excel và dùng hàm tính toán.

a) Khi thay đổi điểm, kết quả tự động cập nhật. [Đúng] *
b) Hàm SUM tìm điểm cao nhất. [Sai] *
c) Hàm MAX tìm điểm cao nhất. [Đúng] *
d) Xóa công thức thì kết quả vẫn cập nhật. [Sai] *

---
```

### 3️⃣ Tự Luận (TL)

**Tự luận đơn giản:**
```
Câu 5: [Mức độ: VD] [Loại: essay] [Điểm: 2.0]
Phân tích sự phát triển máy tính qua các thế hệ.

---
```

**Tự luận có câu con:**
```
Câu 6: [Mức độ: VDC] [Loại: essay] [Điểm: 3.0]
Giải bài toán:

a) (1.0đ) Tìm nghiệm x^2 - 4 = 0
b) (1.0đ) Vẽ đồ thị y = x^2 - 4
c) (1.0đ) Phân tích tính chất hàm số

---
```

---

## Quy Tắc Quan Trọng

### ✅ BẮT BUỘC phải có:

1. **Metadata** (đầu mỗi nhóm):
   ```
   Chủ đề: Tên chủ đề
   Bài học: Tên bài học
   ```

2. **Header câu hỏi**:
   ```
   Câu [số]: [Mức độ: NB/TH/VD/VDC] [Loại: ...]
   ```

3. **Loại câu hỏi** (chọn 1):
   - `single` - TNKQ 1 đáp án
   - `multiple` - TNKQ nhiều đáp án
   - `true_false` - DS đơn giản
   - `true_false_multiple` - DS nhiều ý (a, b, c, d)
   - `essay` - Tự luận

4. **Đáp án** (tùy loại):
   - TNKQ: Đánh dấu `*` sau đáp án hoặc dòng "Đáp án đúng: A"
   - DS nhiều ý: Mỗi ý có `[Đúng]` hoặc `[Sai]` + dấu `*`
   - Tự luận: **KHÔNG CẦN** đáp án, chỉ cần `[Điểm: X.X]`

5. **Phân cách**: Dùng `---` giữa các câu

### ❌ LỖI THƯỜNG GẶP:

| Lỗi | Hậu quả | Cách sửa |
|-----|---------|----------|
| Quên `[Loại: ...]` | Mặc định là `single` | Thêm `[Loại: true_false_multiple]` hoặc `[Loại: essay]` |
| DS nhiều ý thiếu `[Đúng]`/`[Sai]` | Không parse được | Thêm `[Đúng]` hoặc `[Sai]` sau mỗi phát biểu |
| Tự luận thiếu `[Điểm]` | Không biết điểm câu | Thêm `[Điểm: 2.0]` vào header |
| Tự luận có đáp án A), B), C) | Parse sai thành TNKQ | Xóa đáp án A, B, C, D |

---

## So Sánh 3 Loại

| Đặc điểm | TNKQ | DS Nhiều Ý | Tự Luận |
|----------|------|------------|---------|
| **Header** | `[Loại: single/multiple]` | `[Loại: true_false_multiple]` | `[Loại: essay] [Điểm: X.X]` |
| **Đáp án** | A), B), C), D) | a), b), c), d) | Không có |
| **Format đáp án** | `*` hoặc "Đáp án đúng: A" | `[Đúng]/[Sai] *` | Không cần |
| **Ví dụ** | 4 lựa chọn, chọn 1 hoặc nhiều | 4 phát biểu, mỗi ý Đúng/Sai riêng | Câu mở, chấm theo rubric |

---

## Checklist Import

- [ ] File .docx (không phải .doc)
- [ ] Có "Chủ đề:" và "Bài học:"
- [ ] Mỗi câu có "Câu [số]:"
- [ ] Có `[Mức độ: NB/TH/VD/VDC]`
- [ ] Có `[Loại: single/multiple/true_false/true_false_multiple/essay]`
- [ ] **Nếu TNKQ**: Đáp án A-D, đánh dấu `*`
- [ ] **Nếu DS nhiều ý**: a-d, có `[Đúng]/[Sai]`, đánh dấu `*`
- [ ] **Nếu Tự luận**: Có `[Điểm: X.X]`, KHÔNG có A, B, C, D
- [ ] Phân cách bằng `---`

---

## Công Thức Toán Học (LaTeX)

**Inline** (trong câu): `$x^2 + 2x + 1$`

**Display** (độc lập): `$$\frac{-b \pm \sqrt{b^2-4ac}}{2a}$$`

**Ký hiệu thường dùng:**
- Phân số: `$\frac{a}{b}$`
- Căn: `$\sqrt{x}$`, `$\sqrt[3]{x}$`
- Mũ: `$x^2$`, `$x^{2n}$`
- Chỉ số dưới: `$x_1$`, `$x_{i+1}$`
- Hóa học: `$H_2O$`, `$Ca^{2+}$`
- Hy Lạp: `$\alpha, \beta, \Delta, \pi$`

---

## Liên Hệ Hỗ Trợ

Nếu gặp lỗi khi import:
1. Kiểm tra lại checklist
2. Xem file mẫu: Tải Mẫu Word từ hệ thống
3. Đọc hướng dẫn chi tiết: `HUONG_DAN_THEM_TU_WORD.md`
4. Liên hệ admin nếu vẫn lỗi

---

**Lưu ý:** Hệ thống sẽ tự động nhận diện loại câu hỏi dựa vào `[Loại: ...]`. Đảm bảo viết đúng format để tránh lỗi!
