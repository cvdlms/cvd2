# 🔗 Hệ thống Mapping Ma trận ↔ Ngân hàng câu hỏi

## 📋 Mục đích

Kết nối hệ thống **Ma trận đề kiểm tra** với **Ngân hàng câu hỏi** để:
- ✅ Kiểm tra số lượng câu hỏi có sẵn cho mỗi ĐVKT trong ma trận
- ✅ Cảnh báo khi không đủ câu hỏi để tạo đề
- ✅ Tự động tạo đề thi từ ma trận
- ✅ Thống kê câu hỏi theo ĐVKT và mức độ

---

## 🗂️ Cấu trúc File

```
cvd2/teacher/
├── mapping_helper.php         # Các hàm helper cho mapping
├── test_mapping.php           # API endpoint test
├── demo_mapping.html          # Giao diện demo (mở trong browser)
├── MAP_MA_TRAN_VA_NGAN_HANG.md  # Tài liệu chi tiết
└── questions/                 # Ngân hàng câu hỏi
    └── {grade}/
        └── {semester}/
            └── subject_{id}.json
```

---

## 🎯 Nguyên lý Mapping

### Điểm kết nối: **ĐVKT (Đơn vị kiến thức)**

**Ma trận** (matrix_builder.php):
```php
$topics = [
    [
        'title' => 'Chương 1: Phương trình',  // Chủ đề
        'units' => [
            [
                'title' => 'Bài 1: Giải phương trình bậc hai',  // ← ĐVKT
                'so_tiet' => 5,
                'levels' => ['NB' => true, 'TH' => true, 'VD' => true],
                'tnkq_nb' => 2,  // Cần 2 câu NB
                'tnkq_th' => 3   // Cần 3 câu TH
            ]
        ]
    ]
];
```

**Ngân hàng** (subject_1.json):
```json
[
    {
        "topic": "Chương 1: Phương trình",
        "lesson": "Bài 1: Giải phương trình bậc hai",  // ← ĐVKT
        "questions": [
            {
                "question": "Nghiệm của phương trình $x^2 = 4$ là:",
                "level": "NB",
                "type": "single",
                "correct_answers": ["A"]
            }
        ]
    }
]
```

**Mapping:** `unit['title'] === topicData['lesson']`

---

## 🚀 Cách sử dụng

### 1. Demo trực quan

Mở trong trình duyệt:
```
http://localhost/cvd2/teacher/demo_mapping.html
```

Nhập thông tin:
- **Tên ĐVKT:** Bài 1: Giải phương trình bậc hai
- **Khối:** Khối 8
- **Học kỳ:** HK1
- **Môn học ID:** 1

Nhấn **"🔍 Kiểm tra thống kê"** để xem số lượng câu hỏi có sẵn.

---

### 2. Sử dụng trong code PHP

#### A. Lấy thống kê câu hỏi

```php
require_once 'mapping_helper.php';

$matrixUnit = [
    'title' => 'Bài 1: Giải phương trình bậc hai',
    'topic' => 'Chương 1: Phương trình'
];

$stats = getQuestionStats($matrixUnit, 'khoi8', 'hk1', 1);

echo "Tổng số câu: " . $stats['total'];
echo "Câu NB: " . $stats['by_level']['NB'];
echo "Câu TH: " . $stats['by_level']['TH'];
```

**Output:**
```
Tổng số câu: 12
Câu NB: 4
Câu TH: 5
Câu VD: 2
Câu VDC: 1
```

---

#### B. Lấy danh sách câu hỏi

```php
// Lấy TẤT CẢ câu hỏi của ĐVKT
$questions = getQuestionsFromBank($matrixUnit, 'khoi8', 'hk1', 1);

// Lấy chỉ câu hỏi mức NB
$nbQuestions = getQuestionsFromBank($matrixUnit, 'khoi8', 'hk1', 1, 'NB');

// Lấy câu hỏi VD, loại single choice
$vdSingle = getQuestionsFromBank($matrixUnit, 'khoi8', 'hk1', 1, 'VD', 'single');

foreach ($questions as $q) {
    echo $q['question'] . "\n";
    echo "Level: " . $q['level'] . "\n";
}
```

---

#### C. Kiểm tra tính khả thi của Ma trận

```php
$matrixData = [
    'topics' => [
        [
            'title' => 'Chương 1: Phương trình',
            'units' => [
                [
                    'title' => 'Bài 1: Giải phương trình bậc hai',
                    'levels' => ['NB' => true, 'TH' => true],
                    'tnkq_nb' => 5,  // Cần 5 câu NB
                    'tnkq_th' => 3   // Cần 3 câu TH
                ]
            ]
        ]
    ]
];

$warnings = checkMatrixFeasibility($matrixData, 'khoi8', 'hk1', 1);

if (empty($warnings)) {
    echo "✅ Ma trận khả thi! Đủ câu hỏi trong Ngân hàng.";
} else {
    echo "⚠️ Cảnh báo:\n";
    foreach ($warnings as $w) {
        echo "- {$w['unit']}: {$w['message']}\n";
    }
}
```

**Output mẫu:**
```
⚠️ Cảnh báo:
- Bài 1: Giải phương trình bậc hai: Cần 5 câu TNKQ nhưng chỉ có 4 câu
- Bài 2: Hệ phương trình: ĐVKT này không có câu hỏi nào trong Ngân hàng
```

---

#### D. Random chọn câu hỏi

```php
// Chọn ngẫu nhiên 3 câu VD, loại single
$selectedQuestions = randomSelectQuestions(
    $matrixUnit,
    'khoi8',
    'hk1',
    1,
    'VD',    // level
    3,       // số câu cần
    'single' // type
);

foreach ($selectedQuestions as $q) {
    echo $q['question'] . "\n";
}
```

---

#### E. Tạo đề thi tự động từ Ma trận

```php
$matrixData = loadMatrixFromLocalStorage(); // Giả sử đã load từ localStorage

$examResult = createExamFromMatrix($matrixData, 'khoi8', 'hk1', 1);

echo "Đề thi có: " . $examResult['total_questions'] . " câu\n";

if (!empty($examResult['warnings'])) {
    echo "Cảnh báo:\n";
    foreach ($examResult['warnings'] as $w) {
        echo "- {$w['unit']} (level {$w['level']}): Cần {$w['needed']} câu nhưng chỉ có {$w['found']} câu\n";
    }
}

// Hiển thị câu hỏi
foreach ($examResult['questions'] as $index => $q) {
    echo ($index + 1) . ". " . $q['question'] . "\n";
    echo "   (ĐVKT: " . $q['source_unit'] . ")\n\n";
}
```

---

### 3. Sử dụng qua AJAX

#### JavaScript

```javascript
// Lấy thống kê
async function checkStats(unitTitle) {
    const response = await fetch('test_mapping.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'get_stats',
            unit: unitTitle,
            grade: 'khoi8',
            semester: 'hk1',
            subject_id: 1
        })
    });
    
    const stats = await response.json();
    console.log(`Tổng số câu: ${stats.total}`);
    console.log(`NB: ${stats.by_level.NB}`);
}

// Lấy câu hỏi
async function fetchQuestions(unitTitle, level = null) {
    const response = await fetch('test_mapping.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'get_questions',
            unit: unitTitle,
            grade: 'khoi8',
            semester: 'hk1',
            subject_id: 1,
            level: level  // null = all levels
        })
    });
    
    const questions = await response.json();
    return questions;
}

// Kiểm tra tính khả thi
async function checkFeasibility(matrixData) {
    const response = await fetch('test_mapping.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'check_feasibility',
            matrix: matrixData,
            grade: 'khoi8',
            semester: 'hk1',
            subject_id: 1
        })
    });
    
    const result = await response.json();
    if (result.feasible) {
        alert('✅ Ma trận khả thi!');
    } else {
        console.log('Warnings:', result.warnings);
    }
}
```

---

## 🔍 API Endpoints

### POST test_mapping.php

#### 1. `get_stats` - Lấy thống kê

**Request:**
```json
{
    "action": "get_stats",
    "unit": "Bài 1: Giải phương trình bậc hai",
    "topic": "Chương 1: Phương trình",
    "grade": "khoi8",
    "semester": "hk1",
    "subject_id": 1
}
```

**Response:**
```json
{
    "unit": "Bài 1: Giải phương trình bậc hai",
    "topic": "Chương 1: Phương trình",
    "total": 12,
    "by_level": {
        "NB": 4,
        "TH": 5,
        "VD": 2,
        "VDC": 1
    },
    "by_type": {
        "single": 10,
        "multiple": 2
    }
}
```

---

#### 2. `get_questions` - Lấy danh sách câu hỏi

**Request:**
```json
{
    "action": "get_questions",
    "unit": "Bài 1: Giải phương trình bậc hai",
    "grade": "khoi8",
    "semester": "hk1",
    "subject_id": 1,
    "level": "NB",
    "type": "single"
}
```

**Response:**
```json
[
    {
        "question": "Nghiệm của $x^2 = 4$ là:",
        "options": ["A. $x = \\pm 2$", "B. $x = 2$", "C. $x = -2$", "D. $x = 4$"],
        "correct_answers": ["A"],
        "level": "NB",
        "type": "single"
    }
]
```

---

#### 3. `check_feasibility` - Kiểm tra tính khả thi

**Request:**
```json
{
    "action": "check_feasibility",
    "matrix": {
        "topics": [...]
    },
    "grade": "khoi8",
    "semester": "hk1",
    "subject_id": 1
}
```

**Response:**
```json
{
    "feasible": false,
    "warnings": [
        {
            "type": "warning",
            "unit": "Bài 1: Giải phương trình bậc hai",
            "level": "VDC",
            "message": "Không có câu hỏi mức độ VDC"
        }
    ],
    "total_warnings": 1
}
```

---

## 🛠️ Các hàm chính trong mapping_helper.php

| Hàm | Mô tả | Tham số | Trả về |
|-----|-------|---------|--------|
| `normalizeString($str)` | Chuẩn hóa chuỗi để so sánh | string | string |
| `getQuestionsFromBank($unit, $grade, $sem, $subId, $level, $type)` | Lấy câu hỏi từ Ngân hàng | array, strings | array |
| `getQuestionStats($unit, $grade, $sem, $subId)` | Thống kê số lượng câu hỏi | array, strings | array |
| `checkMatrixFeasibility($matrix, $grade, $sem, $subId)` | Kiểm tra tính khả thi | array, strings | array |
| `randomSelectQuestions($unit, $grade, $sem, $subId, $level, $count, $type)` | Chọn ngẫu nhiên câu hỏi | array, strings, int | array |
| `createExamFromMatrix($matrix, $grade, $sem, $subId)` | Tạo đề thi từ ma trận | array, strings | array |

---

## 📊 Use Cases thực tế

### 1. Cảnh báo khi tạo Ma trận

Khi giáo viên đang tạo ma trận trong `matrix_builder.php`, hiển thị số câu hỏi có sẵn:

```php
// Trong matrix_builder.php
foreach ($units as $unit) {
    $stats = getQuestionStats($unit, $grade, $semester, $subjectId);
    
    if ($stats['total'] === 0) {
        echo '<span class="badge badge-danger">⚠️ Chưa có câu hỏi</span>';
    } else {
        echo '<span class="badge badge-success">✅ ' . $stats['total'] . ' câu</span>';
        echo '<small>(NB: ' . $stats['by_level']['NB'] . ', ';
        echo 'TH: ' . $stats['by_level']['TH'] . ', ';
        echo 'VD: ' . $stats['by_level']['VD'] . ')</small>';
    }
}
```

---

### 2. Tự động tạo đề thi

Khi giáo viên nhấn "Tạo đề từ ma trận":

```php
// Trong exam_generator.php
$matrix = $_SESSION['current_matrix'];

$examResult = createExamFromMatrix($matrix, $grade, $semester, $subjectId);

if (!empty($examResult['warnings'])) {
    $_SESSION['warnings'] = $examResult['warnings'];
}

$_SESSION['exam_questions'] = $examResult['questions'];
header('Location: exam_preview.php');
```

---

### 3. Kiểm tra trước khi lưu Ma trận

```php
// Trước khi lưu ma trận
$warnings = checkMatrixFeasibility($newMatrix, $grade, $semester, $subjectId);

if (!empty($warnings)) {
    $errors = array_filter($warnings, fn($w) => $w['type'] === 'error');
    
    if (!empty($errors)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Ma trận không khả thi. Một số ĐVKT không có câu hỏi.',
            'errors' => $errors
        ]);
        exit;
    }
}
```

---

## ⚙️ Cấu hình và Tùy chỉnh

### 1. Điều chỉnh thuật toán so khớp

Nếu muốn so khớp linh hoạt hơn (bỏ qua "Bài 1: "):

```php
function normalizeString($str) {
    $str = mb_strtolower(trim($str), 'UTF-8');
    
    // Bỏ "Bài X: " ở đầu
    $str = preg_replace('/^bài\s+\d+:\s*/iu', '', $str);
    
    // Bỏ khoảng trắng thừa
    $str = preg_replace('/\s+/', ' ', $str);
    
    return $str;
}
```

---

### 2. Thêm field `unit_code` để mapping chính xác

**Ma trận:**
```php
'units' => [
    [
        'unit_code' => 'TOAN8_C1_B1',  // ← Thêm mã ĐVKT
        'title' => 'Bài 1: Giải phương trình bậc hai'
    ]
]
```

**Ngân hàng:**
```json
{
    "lesson": "Bài 1: Giải phương trình bậc hai",
    "lesson_code": "TOAN8_C1_B1",  // ← Thêm mã ĐVKT
    "questions": [...]
}
```

**Update mapping:**
```php
function getQuestionsFromBank(...) {
    foreach ($bankData as $topicData) {
        // Ưu tiên so khớp bằng code
        if (isset($matrixUnit['unit_code']) && isset($topicData['lesson_code'])) {
            if ($matrixUnit['unit_code'] === $topicData['lesson_code']) {
                // Match!
            }
        }
        // Fallback: so khớp bằng title
        else if (normalizeString($topicData['lesson']) === normalizeString($matrixUnit['title'])) {
            // Match!
        }
    }
}
```

---

## 🐛 Troubleshooting

### Lỗi: "Không tìm thấy câu hỏi" nhưng chắc chắn có trong file

**Nguyên nhân:** Tên ĐVKT trong Ma trận và Ngân hàng không khớp chính xác

**Giải pháp:**
```php
// Debug: In ra để so sánh
echo "Matrix unit: [" . normalizeString($matrixUnit['title']) . "]\n";
echo "Bank lesson: [" . normalizeString($topicData['lesson']) . "]\n";
```

Kiểm tra:
- Khoảng trắng thừa
- Ký tự đặc biệt (dấu `–` vs `-`)
- Chữ hoa/thường
- Số thứ tự (Bài 01 vs Bài 1)

---

### Lỗi: "File không tồn tại"

**Nguyên nhân:** Đường dẫn file JSON sai

**Giải pháp:**
```php
$bankFile = __DIR__ . "/questions/{$grade}/{$semester}/subject_{$subjectId}.json";
echo "Checking file: $bankFile\n";
echo "File exists: " . (file_exists($bankFile) ? 'YES' : 'NO') . "\n";
```

---

## 📚 Tài liệu liên quan

- **FORMAT_CAU_HOI_WORD.md** - Định dạng câu hỏi Word
- **CAU_TRUC_DU_LIEU.md** - Cấu trúc dữ liệu JSON
- **MAP_MA_TRAN_VA_NGAN_HANG.md** - Tài liệu mapping chi tiết
- **HUONG_DAN_THEM_TU_WORD.md** - Hướng dẫn import từ Word

---

## 🎉 Hoàn thành!

Bây giờ bạn đã có:
1. ✅ Hệ thống mapping hoàn chỉnh
2. ✅ Các hàm helper để tích hợp vào code
3. ✅ Giao diện demo để test
4. ✅ API endpoints để gọi từ JavaScript
5. ✅ Tài liệu đầy đủ với ví dụ

**Khuyến nghị tiếp theo:**
1. Tích hợp `getQuestionStats()` vào `matrix_builder.php`
2. Thêm nút "Tạo đề từ ma trận" sử dụng `createExamFromMatrix()`
3. Hiển thị cảnh báo khi ĐVKT không có câu hỏi
4. (Tùy chọn) Implement `unit_code` / `lesson_code` cho mapping chính xác hơn

---

📧 **Liên hệ:** Nếu gặp vấn đề, xem lại phần Troubleshooting hoặc kiểm tra console log.
