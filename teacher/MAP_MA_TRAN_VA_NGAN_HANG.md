# MAPPING MA TRẬN VÀ NGÂN HÀNG CÂU HỎI

## Tổng Quan

Hệ thống có thể map giữa **Ma trận đề thi** và **Ngân hàng câu hỏi** thông qua **Đơn vị kiến thức (ĐVKT)**.

## Cấu Trúc Dữ Liệu

### 1. Ma Trận Đề Thi

```javascript
{
  "topics": [
    {
      "title": "Chương 1: Phương trình bậc hai",  // Chủ đề
      "units": [
        {
          "title": "Bài 1: Giải phương trình bậc hai",  // ĐVKT
          "so_tiet": 4,  // Số tiết
          "levels": {
            "NB": true,
            "TH": true,
            "VD": true
          }
        },
        {
          "title": "Bài 2: Hệ thức Vi-et",
          "so_tiet": 3,
          "levels": {...}
        }
      ]
    }
  ]
}
```

### 2. Ngân Hàng Câu Hỏi

```json
[
  {
    "topic": "Chương 1: Phương trình bậc hai",  // Chủ đề
    "lesson": "Bài 1: Giải phương trình bậc hai",  // ĐVKT
    "questions": [...]
  }
]
```

## Mapping Strategy

### Cách 1: Mapping trực tiếp theo tên (String Match)

**Ưu điểm:** Đơn giản, dễ triển khai
**Nhược điểm:** Nhạy cảm với khoảng trắng, chữ hoa/thường

```javascript
// Ma trận
unit.title = "Bài 1: Giải phương trình bậc hai"

// Ngân hàng câu hỏi
lesson = "Bài 1: Giải phương trình bậc hai"

// Mapping
if (unit.title === lesson) {
  // Matched!
}
```

### Cách 2: Mapping theo chuẩn hóa (Normalized Match)

**Ưu điểm:** Linh hoạt hơn, chống lỗi đánh máy
**Nhược điểm:** Phức tạp hơn

```javascript
function normalize(str) {
  return str.trim()
    .toLowerCase()
    .replace(/\s+/g, ' ')  // Normalize spaces
    .replace(/[àáảãạăắằẳẵặâấầẩẫậ]/g, 'a')  // Normalize Vietnamese
    // ... more normalization
}

// Mapping
if (normalize(unit.title) === normalize(lesson)) {
  // Matched!
}
```

### Cách 3: Mapping theo mã ĐVKT (ID-based)

**Ưu điểm:** Chính xác nhất, không sợ đổi tên
**Nhược điểm:** Cần thêm field mã ĐVKT vào cả 2 hệ thống

```json
// Ma trận
{
  "unit_id": "TOAN8_C1_B1",
  "title": "Bài 1: Giải phương trình bậc hai"
}

// Ngân hàng
{
  "lesson_id": "TOAN8_C1_B1",
  "lesson": "Bài 1: Giải phương trình bậc hai"
}
```

## Đề Xuất Triển Khai

### Option A: Giữ nguyên - Mapping theo tên (Khuyến nghị cho hiện tại)

**Không cần thay đổi cấu trúc**, chỉ cần viết hàm mapping:

```php
// mapping_helper.php

/**
 * Lấy câu hỏi từ Ngân hàng dựa trên Ma trận
 * 
 * @param array $matrixUnit - Unit từ ma trận {"title": "Bài 1: ...", "topic": "Chương 1: ..."}
 * @param string $grade - khoi6, khoi7, khoi8, khoi9
 * @param string $semester - hk1, hk2
 * @param int $subjectId - ID môn học
 * @param string $level - NB, TH, VD, VDC
 * @return array - Danh sách câu hỏi phù hợp
 */
function getQuestionsFromBank($matrixUnit, $grade, $semester, $subjectId, $level) {
    $bankFile = __DIR__ . "/questions/{$grade}/{$semester}/subject_{$subjectId}.json";
    
    if (!file_exists($bankFile)) {
        return [];
    }
    
    $bankData = json_decode(file_get_contents($bankFile), true) ?: [];
    $matchedQuestions = [];
    
    foreach ($bankData as $topicData) {
        // So sánh Topic (tùy chọn - có thể bỏ qua nếu chỉ cần lesson)
        if (isset($matrixUnit['topic']) && 
            normalize($topicData['topic']) !== normalize($matrixUnit['topic'])) {
            continue;
        }
        
        // So sánh Lesson (ĐVKT) - QUAN TRỌNG
        if (normalize($topicData['lesson']) !== normalize($matrixUnit['title'])) {
            continue;
        }
        
        // Lọc câu hỏi theo level
        foreach ($topicData['questions'] as $question) {
            if ($question['level'] === $level) {
                $matchedQuestions[] = $question;
            }
        }
    }
    
    return $matchedQuestions;
}

/**
 * Chuẩn hóa tên để so sánh
 */
function normalize($str) {
    return trim(strtolower($str));
}

/**
 * Lấy thống kê câu hỏi trong Ngân hàng theo ĐVKT và level
 */
function getQuestionStats($matrixUnit, $grade, $semester, $subjectId) {
    $questions = [];
    foreach (['NB', 'TH', 'VD', 'VDC'] as $level) {
        $questions[$level] = getQuestionsFromBank($matrixUnit, $grade, $semester, $subjectId, $level);
    }
    
    return [
        'unit' => $matrixUnit['title'],
        'total' => array_sum(array_map('count', $questions)),
        'by_level' => [
            'NB' => count($questions['NB']),
            'TH' => count($questions['TH']),
            'VD' => count($questions['VD']),
            'VDC' => count($questions['VDC'])
        ]
    ];
}
```

### Option B: Thêm mã ĐVKT (Dài hạn - chưa cần thiết)

**Cần thay đổi cấu trúc:**

1. **Thêm field vào Ma trận:**
```javascript
{
  "title": "Bài 1: Giải phương trình bậc hai",
  "unit_code": "C1_B1"  // Thêm mã
}
```

2. **Thêm field vào Ngân hàng:**
```json
{
  "topic": "Chương 1: Phương trình bậc hai",
  "lesson": "Bài 1: Giải phương trình bậc hai",
  "lesson_code": "C1_B1",  // Thêm mã
  "questions": [...]
}
```

## Use Cases Thực Tế

### 1. Kiểm tra tính khả thi khi tạo Ma trận

```javascript
// Trong matrix_builder.php, khi generate ma trận
function checkQuestionAvailability(matrixData, grade, semester, subjectId) {
  const warnings = [];
  
  matrixData.topics.forEach(topic => {
    topic.units.forEach(unit => {
      const stats = getQuestionStats({
        title: unit.title,
        topic: topic.title
      }, grade, semester, subjectId);
      
      // Kiểm tra có đủ câu hỏi không
      ['NB', 'TH', 'VD', 'VDC'].forEach(level => {
        if (unit.levels[level] && stats.by_level[level] === 0) {
          warnings.push({
            unit: unit.title,
            level: level,
            message: `Không có câu hỏi ${level} cho ĐVKT này`
          });
        }
      });
    });
  });
  
  return warnings;
}
```

### 2. Tự động tạo đề từ Ma trận

```php
// create_exam_from_matrix.php

function createExamFromMatrix($matrixData, $grade, $semester, $subjectId) {
    $exam = [];
    
    foreach ($matrixData['topics'] as $topic) {
        foreach ($topic['units'] as $unit) {
            // Lấy số câu hỏi cần thiết cho mỗi level
            $needed = [
                'NB' => $unit['tnkq_nb'] ?? 0,
                'TH' => $unit['tnkq_th'] ?? 0,
                'VD' => $unit['tnkq_vd'] ?? 0
            ];
            
            foreach ($needed as $level => $count) {
                if ($count > 0) {
                    $questions = getQuestionsFromBank([
                        'title' => $unit['title'],
                        'topic' => $topic['title']
                    ], $grade, $semester, $subjectId, $level);
                    
                    // Random chọn $count câu
                    $selected = array_rand($questions, min($count, count($questions)));
                    
                    foreach ((array)$selected as $idx) {
                        $exam[] = $questions[$idx];
                    }
                }
            }
        }
    }
    
    return $exam;
}
```

### 3. Hiển thị thống kê trong giao diện Ma trận

```javascript
// Thêm vào matrix_builder.php
async function showQuestionStats(unitTitle, topicTitle) {
  const response = await fetch('get_question_stats.php', {
    method: 'POST',
    body: JSON.stringify({
      unit: unitTitle,
      topic: topicTitle,
      grade: selectedGrade,
      semester: selectedSemester,
      subject_id: selectedSubjectId
    })
  });
  
  const stats = await response.json();
  
  // Hiển thị tooltip hoặc badge
  return `
    <div class="question-stats">
      <span class="badge bg-info">NB: ${stats.by_level.NB}</span>
      <span class="badge bg-success">TH: ${stats.by_level.TH}</span>
      <span class="badge bg-warning">VD: ${stats.by_level.VD}</span>
      <span class="badge bg-danger">VDC: ${stats.by_level.VDC}</span>
    </div>
  `;
}
```

## Kết Luận

**✅ CÓ THỂ mapping được giữa Ma trận và Ngân hàng câu hỏi**

**Cách mapping:**
1. **Topic → Topic** (cấp độ chương/phần)
2. **Unit.title → Lesson** (ĐVKT - quan trọng nhất)
3. **Levels (NB/TH/VD/VDC)** - lọc câu hỏi theo mức độ

**Đề xuất:**
- ✅ **Ngắn hạn**: Dùng string matching với chuẩn hóa đơn giản
- ✅ **Trung hạn**: Thêm tính năng kiểm tra số lượng câu hỏi khi tạo ma trận
- ✅ **Dài hạn**: Thêm mã ĐVKT nếu cần độ chính xác cao

**Ưu điểm của cấu trúc hiện tại:**
- Dễ hiểu, dễ sử dụng
- Không cần cấu trúc phức tạp
- Linh hoạt, dễ mở rộng

---

**File liên quan:**
- [CAU_TRUC_DU_LIEU.md](CAU_TRUC_DU_LIEU.md) - Cấu trúc Ngân hàng câu hỏi
- [matrix_builder.php](matrix_builder.php) - Code xây dựng ma trận
- [question_bank.php](question_bank.php) - Quản lý ngân hàng câu hỏi
