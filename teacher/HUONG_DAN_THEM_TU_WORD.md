# HƯỚNG DẪN SỬ DỤNG TÍNH NĂNG "THÊM TỪ WORD"

## Tổng Quan

Tính năng "Thêm từ Word" cho phép giáo viên nhập hàng loạt câu hỏi trắc nghiệm từ file Microsoft Word (.docx) vào Ngân hàng câu hỏi. Tính năng này hỗ trợ:

✅ Câu hỏi trắc nghiệm 1 đáp án đúng (single choice)
✅ Câu hỏi trắc nghiệm nhiều đáp án đúng (multiple choice)
✅ Công thức toán học (LaTeX format với MathJax)
✅ Công thức hóa học, vật lý
✅ 4 mức độ câu hỏi: NB, TH, VD, VDC
✅ Tự động nhận diện câu hỏi và đáp án
✅ Gộp hoặc ghi đè câu hỏi hiện có

---

## Cách Sử Dụng

### Bước 1: Chuẩn Bị File Word

1. **Tải file mẫu Word**:
   - Trong trang Quản Lý Câu Hỏi
   - Chọn Khối, Môn học, Học kỳ
   - Nhấn nút **"📥 Tải Mẫu Word"**
   - File `mau_cau_hoi_word.docx` sẽ được tải về

2. **Hoặc tạo file mới** theo format chuẩn (xem phần [Format Chuẩn](#format-chuẩn))

### Bước 2: Soạn Câu Hỏi Trong Word

Mở file Word và soạn câu hỏi theo format chuẩn. Ví dụ:

```
Chủ đề: Chương 1: Phương trình bậc hai
Bài học: Bài 1: Giải phương trình bậc hai (Đơn vị kiến thức)

Câu 1: [Mức độ: NB] [Loại: single]
Phương trình bậc hai một ẩn có dạng tổng quát là:

A) $ax + b = 0$
B) $ax^2 + bx + c = 0$ *
C) $ax^3 + bx^2 + cx + d = 0$
D) $\frac{a}{x} + b = 0$

Đáp án đúng: B

---

Câu 2: [Mức độ: VDC] [Loại: multiple]
Cho phương trình $x^2 + 2x + 1 = 0$. Chọn các phát biểu đúng:

A) Phương trình có nghiệm kép $x = -1$ *
B) $\Delta = 0$ *
C) Phương trình có 2 nghiệm phân biệt
D) Tổng hai nghiệm bằng $-2$ *

Đáp án đúng: A, B, D

---
```

### Bước 3: Import Vào Hệ Thống

1. Lưu file Word (định dạng `.docx`)
2. Trong trang Quản Lý Câu Hỏi, nhấn nút **"📄 Thêm từ Word"**
3. Trong modal hiện ra:
   - Nhấn **"Chọn File Word"** và chọn file `.docx` của bạn
   - (Tùy chọn) Tick **"Ghi đè câu hỏi hiện có"** nếu muốn thay thế câu hỏi cũ
   - Nhấn **"📤 Import Câu Hỏi"**
4. Hệ thống sẽ xử lý và hiển thị thông báo kết quả

### Bước 4: Kiểm Tra Kết Quả

- Sau khi import thành công, các câu hỏi sẽ xuất hiện trong danh sách
- Xem lại để đảm bảo:
  - Câu hỏi hiển thị đúng
  - Đáp án được nhận diện chính xác
  - Công thức toán học render đúng (với MathJax)
  - Chủ đề và Bài học được phân loại đúng

---

## Format Chuẩn

### 1. Metadata (Thông tin chung)

```
Chủ đề: Tên chủ đề (phạm vi rộng - chương, phần)
Bài học: Tên bài học (Đơn vị kiến thức cụ thể)
```

**Lưu ý:**
- **Chủ đề**: Phân loại ở cấp độ cao (Chương, Phần)
- **Bài học**: Đơn vị kiến thức (ĐVKT) cụ thể mà câu hỏi thuộc về
- Đặt ở đầu file hoặc trước mỗi nhóm câu hỏi mới
- Nếu không có metadata mới, hệ thống sẽ dùng metadata của nhóm trước

### 2. Câu Hỏi

```
Câu [số]: [Mức độ: NB/TH/VD/VDC] [Loại: single/multiple]
Nội dung câu hỏi...
```

**Các thành phần:**
- `Câu [số]:` - Bắt buộc để nhận diện câu hỏi (số có thể bất kỳ)
- `[Mức độ: ...]` - Không bắt buộc, mặc định: NB
  - **NB**: Nhận biết
  - **TH**: Thông hiểu
  - **VD**: Vận dụng
  - **VDC**: Vận dụng cao
- `[Loại: ...]` - Không bắt buộc, mặc định: single
  - **single**: Trắc nghiệm 1 đáp án đúng
  - **multiple**: Trắc nghiệm nhiều đáp án đúng

### 3. Đáp Án

```
A) Đáp án thứ nhất
B) Đáp án thứ hai *
C) Đáp án thứ ba
D) Đáp án thứ tư
```

**Quy tắc:**
- Bắt đầu bằng `A)`, `B)`, `C)`, `D)`, ... hoặc `A.`, `B.`, `C.`, `D.`, ...
- Đánh dấu đáp án đúng bằng `*` ở cuối
- **Hoặc** dùng dòng riêng: `Đáp án đúng: B` hoặc `Đáp án đúng: A, C, D`

### 4. Phân Cách

```
---
```

- Dùng 3 dấu gạch ngang (`---`) để phân cách các câu hỏi
- Có thể dùng dòng trống để phân cách

### 5. Công Thức Toán Học

#### Inline (trong câu)
```
Tính giá trị $x^2 + 2x + 1$ khi $x = 3$
```

#### Display (độc lập)
```
Giải phương trình:

$$x^2 - 5x + 6 = 0$$
```

**Các ký hiệu thường dùng:**

| Loại | LaTeX | Hiển thị |
|------|-------|----------|
| Phân số | `$\frac{a}{b}$` | a/b |
| Căn bậc 2 | `$\sqrt{x}$` | √x |
| Căn bậc n | `$\sqrt[n]{x}$` | ⁿ√x |
| Mũ | `$x^2$` | x² |
| Chỉ số dưới | `$x_1$` | x₁ |
| Góc | `$\angle ABC$` | ∠ABC |
| Tam giác | `$\triangle ABC$` | △ABC |
| Độ | `$90^\circ$` | 90° |
| Ion hóa học | `$Ca^{2+}$` | Ca²⁺ |
| Phân tử | `$H_2O$` | H₂O |
| Vector | `$\vec{F}$` | F⃗ |
| Delta | `$\Delta T$` | ΔT |
| Tổng | `$\sum_{i=1}^{n} x_i$` | Σ |
| Tích phân | `$\int_{a}^{b} f(x) dx$` | ∫ |

Xem thêm trong file [FORMAT_CAU_HOI_WORD.md](FORMAT_CAU_HOI_WORD.md)

---

## Ví Dụ Hoàn Chỉnh

### Ví Dụ 1: Toán Học

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

### Ví Dụ 2: Hóa Học

```
Chủ đề: Chương 2: Hóa học cơ bản
Bài học: Bài 1: Cấu tạo nguyên tử

Câu 1: [Mức độ: NB] [Loại: single]
Công thức hóa học của nước là:

A) $H_2O$ *
B) $H_2O_2$
C) $HO$
D) $H_3O$

Đáp án đúng: A

---

Câu 2: [Mức độ: TH] [Loại: single]
Ion canxi có công thức là:

A) $Ca^+$
B) $Ca^{2+}$ *
C) $Ca^{3+}$
D) $Ca^-$

Đáp án đúng: B

---

Câu 3: [Mức độ: VD] [Loại: single]
Phương trình hóa học nào sau đây cân bằng đúng:

A) $H_2 + O_2 \rightarrow H_2O$
B) $2H_2 + O_2 \rightarrow 2H_2O$ *
C) $H_2 + 2O_2 \rightarrow H_2O$
D) $2H_2 + 2O_2 \rightarrow 2H_2O$

Đáp án đúng: B

---
```

### Ví Dụ 3: Vật Lý

```
Chủ đề: Chương 3: Động học
Bài học: Bài 1: Chuyển động thẳng đều

Câu 1: [Mức độ: NB] [Loại: single]
Gia tốc trọng trường trên Trái Đất có giá trị xấp xỉ:

A) $9.8 m/s$
B) $9.8 m/s^2$ *
C) $98 m/s^2$
D) $0.98 m/s^2$

Đáp án đúng: B

---

Câu 2: [Mức độ: TH] [Loại: single]
Công thức tính vận tốc trong chuyển động thẳng đều là:

A) $v = \frac{s}{t}$ *
B) $v = st$
C) $v = \frac{t}{s}$
D) $v = s + t$

Đáp án đúng: A

---

Câu 3: [Mức độ: VD] [Loại: multiple]
Trong chuyển động thẳng đều, các đại lượng nào sau đây không đổi:

A) Vận tốc *
B) Quãng đường
C) Thời gian
D) Tốc độ *

Đáp án đúng: A, D

---
```

---

## Xử Lý Lỗi

### Lỗi: "Không tìm thấy câu hỏi nào"

**Nguyên nhân:**
- File không có câu hỏi bắt đầu bằng "Câu [số]:"
- Format không đúng chuẩn

**Giải pháp:**
- Kiểm tra lại mỗi câu hỏi phải bắt đầu bằng `Câu 1:`, `Câu 2:`, ...
- Tải file mẫu và làm theo đúng format

### Lỗi: "Lỗi khi đọc file Word"

**Nguyên nhân:**
- File không phải định dạng `.docx`
- File bị lỗi hoặc corrupt

**Giải pháp:**
- Chỉ sử dụng file `.docx` (Word 2007 trở lên)
- Thử lưu lại file hoặc tạo file mới
- Không dùng file `.doc` cũ

### Lỗi: Đáp án không được nhận diện

**Nguyên nhân:**
- Không có dấu `*` sau đáp án đúng
- Không có dòng "Đáp án đúng:"

**Giải pháp:**
- Đánh dấu `*` sau mỗi đáp án đúng: `B) Đáp án này *`
- Hoặc thêm dòng: `Đáp án đúng: B` sau các đáp án

### Lỗi: Công thức toán không hiển thị đúng

**Nguyên nhân:**
- Không dùng ký hiệu `$...$` bao quanh công thức
- Syntax LaTeX sai

**Giải pháp:**
- Luôn bao công thức trong `$...$`: `$x^2 + 1$`
- Kiểm tra syntax LaTeX: [LaTeX Math Symbols](https://www.overleaf.com/learn/latex/List_of_Greek_letters_and_math_symbols)
- Test công thức tại: [MathJax Demo](https://www.mathjax.org/#demo)

---

## Mẹo & Lưu Ý

### ✅ Nên Làm

- Luôn tải file mẫu Word để tham khảo format
- Đánh số câu hỏi liên tục (Câu 1, Câu 2, ...) cho dễ theo dõi
- Kiểm tra kỹ đáp án đúng đã được đánh dấu `*`
- Dùng `---` để phân cách rõ ràng giữa các câu
- Test file nhỏ (5-10 câu) trước khi import file lớn
- Backup file Word để tránh mất dữ liệu

### ❌ Không Nên

- Không dùng file `.doc` (Word 2003)
- Không quên đánh dấu đáp án đúng
- Không dùng ký tự đặc biệt trong Chủ đề/Bài học
- Không quên bao công thức trong `$...$`
- Không copy-paste từ PDF (có thể bị lỗi encoding)

### 💡 Mẹo Nâng Cao

1. **Import nhiều chủ đề cùng lúc**: Trong 1 file Word, có thể có nhiều nhóm Chủ đề/Bài học khác nhau
2. **Ghi đè vs Gộp**: Tick "Ghi đè" để thay thế hoàn toàn, bỏ tick để gộp thêm câu hỏi
3. **Kiểm tra nhanh**: Sau import, dùng chức năng "Xuất Câu Hỏi" để kiểm tra JSON
4. **Tái sử dụng**: Lưu file Word các kỳ trước để tái sử dụng và chỉnh sửa

---

## Hỗ Trợ

Nếu gặp vấn đề, vui lòng:
1. Đọc kỹ file [FORMAT_CAU_HOI_WORD.md](FORMAT_CAU_HOI_WORD.md)
2. Tải và xem file mẫu Word
3. Liên hệ quản trị viên hệ thống

---

**Phiên bản:** 1.0  
**Ngày cập nhật:** 2026-03-11
