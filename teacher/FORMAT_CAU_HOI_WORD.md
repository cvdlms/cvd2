# Hướng Dẫn Format Chuẩn Câu Hỏi Trong Word

## Format Chuẩn

### 1. Cấu Trúc Metadata (Đầu tài liệu hoặc trước mỗi nhóm câu hỏi)

```
Chủ đề: Chủ đề 1: Máy tính và cộng đồng
Bài học: Bài 1: Thiết bị vào và thiết bị ra
```

### 2. Cấu Trúc Câu Hỏi

```
Câu 1: [Mức độ: NB] [Loại: single]
Thiết bị nào sau đây là thiết bị vào?

A) Bàn phím
B) Màn hình
C) Loa
D) Máy in

Đáp án đúng: A

---

Câu 2: [Mức độ: TH] [Loại: multiple]
Các thiết bị nào sau đây là thiết bị ra?

A) Bàn phím
B) Màn hình *
C) Loa *
D) Máy in *

Đáp án đúng: B, C, D

---
```

### 3. Quy Tắc Format

#### Metadata
- **Chủ đề**: Bắt đầu bằng "Chủ đề:" theo sau là tên chủ đề
- **Bài học**: Bắt đầu bằng "Bài học:" theo sau là tên bài học
- Metadata có thể đặt ở đầu document hoặc trước mỗi nhóm câu hỏi

#### Câu Hỏi
- **Bắt đầu**: "Câu [số]:" hoặc "Question [số]:"
- **Mức độ**: [Mức độ: NB/TH/VD/VDC] (không bắt buộc, mặc định: NB)
  - NB: Nhận biết
  - TH: Thông hiểu
  - VD: Vận dụng
  - VDC: Vận dụng cao
- **Loại**: [Loại: single/multiple] (không bắt buộc, mặc định: single)
  - single: Trắc nghiệm 1 đáp án
  - multiple: Trắc nghiệm nhiều đáp án
- **Nội dung câu hỏi**: Dòng tiếp theo sau dòng "Câu X:"

#### Đáp Án
- Bắt đầu bằng **A), B), C), D)** hoặc **A., B., C., D.** hoặc **A. , B. , C. , D.**
- Đáp án đúng có thể đánh dấu bằng:
  - Dấu **\*** ở cuối: `A) Đáp án đúng *`
  - Hoặc dòng riêng: `Đáp án đúng: A` hoặc `Đáp án đúng: A, C` (nhiều đáp án)
- Dấu phân cách giữa các câu hỏi: `---` (3 dấu gạch ngang)

### 4. Công Thức Toán Học

#### Công Thức Inline (trong câu)
Sử dụng ký hiệu `$...$` cho công thức LaTeX:

```
Câu 3: [Mức độ: VD]
Tính giá trị của biểu thức $x^2 + 2x + 1$ khi $x = 3$

A) 14
B) 16 *
C) 18
D) 20

Đáp án đúng: B
```

#### Công Thức Riêng Dòng (độc lập)
Sử dụng `$$...$$` cho công thức LaTeX hiển thị riêng:

```
Câu 4: [Mức độ: VDC]
Giải phương trình sau:

$$x^2 - 5x + 6 = 0$$

A) $x = 1$ hoặc $x = 6$
B) $x = 2$ hoặc $x = 3$ *
C) $x = -2$ hoặc $x = -3$
D) Vô nghiệm

Đáp án đúng: B
```

### 5. Các Công Thức Toán Học Thường Dùng

#### Toán Học
- Phân số: `$\frac{a}{b}$` → $\frac{a}{b}$
- Căn bậc 2: `$\sqrt{x}$` → $\sqrt{x}$
- Căn bậc n: `$\sqrt[n]{x}$` → $\sqrt[n]{x}$
- Mũ: `$x^2$` → $x^2$
- Chỉ số dưới: `$x_1$` → $x_1$
- Tổng: `$\sum_{i=1}^{n} x_i$` → $\sum_{i=1}^{n} x_i$
- Tích phân: `$\int_{a}^{b} f(x) dx$` → $\int_{a}^{b} f(x) dx$

#### Hình Học
- Góc: `$\angle ABC$` → $\angle ABC$
- Tam giác: `$\triangle ABC$` → $\triangle ABC$
- Độ: `$90^\circ$` → $90^\circ$
- Song song: `$AB \parallel CD$` → $AB \parallel CD$
- Vuông góc: `$AB \perp CD$` → $AB \perp CD$

#### Hóa Học
- Phân tử: `$H_2O$` → $H_2O$
- Ion: `$Ca^{2+}$` → $Ca^{2+}$
- Phương trình: `$2H_2 + O_2 \rightarrow 2H_2O$`

#### Vật Lý
- Vector: `$\vec{F}$` → $\vec{F}$
- Delta: `$\Delta T$` → $\Delta T$
- Đơn vị: `$m/s^2$` → $m/s^2$

### 6. Ví Dụ Hoàn Chỉnh

```
Chủ đề: Chương 1: Phương trình bậc hai
Bài học: Bài 1: Giải phương trình bậc hai

Câu 1: [Mức độ: NB] [Loại: single]
Phương trình bậc hai một ẩn có dạng tổng quát là:

A) $ax + b = 0$
B) $ax^2 + bx + c = 0$ *
C) $ax^3 + bx^2 + cx + d = 0$
D) $\frac{a}{x} + b = 0$

Đáp án đúng: B

---

Câu 2: [Mức độ: TH] [Loại: single]
Để phương trình $ax^2 + bx + c = 0$ có hai nghiệm phân biệt thì:

A) $\Delta > 0$ *
B) $\Delta = 0$
C) $\Delta < 0$
D) $\Delta \geq 0$

Đáp án đúng: A

---

Câu 3: [Mức độ: VD] [Loại: single]
Giải phương trình $x^2 - 5x + 6 = 0$

A) $x_1 = 1, x_2 = 6$
B) $x_1 = 2, x_2 = 3$ *
C) $x_1 = -2, x_2 = -3$
D) Vô nghiệm

Đáp án đúng: B

---

Câu 4: [Mức độ: VDC] [Loại: multiple]
Cho phương trình $x^2 + 2x + 1 = 0$. Chọn các phát biểu đúng:

A) Phương trình có nghiệm kép $x = -1$ *
B) $\Delta = 0$ *
C) Phương trình có 2 nghiệm phân biệt
D) Tổng hai nghiệm bằng $-2$ *

Đáp án đúng: A, B, D

---
```

### 7. Lưu Ý Quan Trọng

1. **Encoding**: Lưu file Word với encoding UTF-8 để hỗ trợ tiếng Việt
2. **Công thức**: Luôn dùng ký hiệu `$...$` hoặc `$$...$$` cho công thức LaTeX
3. **Dấu phân cách**: Dùng `---` để phân cách các câu hỏi
4. **Đáp án đúng**: Phải có dòng "Đáp án đúng:" hoặc đánh dấu `*` sau đáp án
5. **Metadata**: Nếu không có metadata mới, hệ thống sẽ dùng metadata của nhóm câu hỏi trước
6. **Số thứ tự câu**: Không bắt buộc phải liên tục, hệ thống sẽ tự động sắp xếp lại

### 8. Tải File Mẫu

Sau khi chọn khối, môn học và học kỳ trong trang Quản Lý Câu Hỏi, nhấn nút **"Tải File Mẫu Word"** để tải file mẫu có sẵn format chuẩn.

### 9. Import Câu Hỏi

1. Chuẩn bị file Word theo format trên
2. Chọn khối, môn học, học kỳ
3. Nhấn nút **"Thêm từ Word"**
4. Chọn file Word (.docx)
5. Hệ thống sẽ tự động:
   - Đọc và phân tích nội dung
   - Nhận diện câu hỏi, đáp án
   - Chuyển đổi công thức LaTeX
   - Lưu vào ngân hàng câu hỏi

### 10. Xử Lý Lỗi

Nếu import thất bại, kiểm tra:
- File có đúng định dạng .docx?
- Câu hỏi có bắt đầu bằng "Câu [số]:"?
- Đáp án có bắt đầu bằng A), B), C), D)?
- Đáp án đúng có được đánh dấu?
- Công thức có được bao trong `$...$`?
