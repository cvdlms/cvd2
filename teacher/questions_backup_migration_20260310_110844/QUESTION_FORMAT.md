# Định dạng file JSON câu hỏi

## Mục đích
File JSON câu hỏi được sử dụng để lưu trữ ngân hàng câu hỏi theo **Chủ đề** và **Đơn vị kiến thức** phù hợp với **Bản đặc tả kỹ thuật**.

## Cấu trúc JSON

### Cấu trúc tổng thể
```json
[
  {
    "topic_name": "Chủ đề F. Giải quyết vấn đề với sự trợ giúp của máy tính",
    "unit_name": "Lập trình trực quan",
    "questions": [
      {
        "question": "Nội dung câu hỏi",
        "options": ["Đáp án A", "Đáp án B", "Đáp án C", "Đáp án D"],
        "correct": 0,
        "type": "single",
        "level": "NB"
      }
    ]
  }
]
```

## Chi tiết các trường

### Cấp độ Topic
| Trường | Kiểu | Bắt buộc | Mô tả |
|--------|------|----------|-------|
| `topic_name` | string | ✅ | Tên chủ đề (phải khớp với `content` trong Bản đặc tả) |
| `unit_name` | string | ✅ | Tên đơn vị kiến thức (phải khớp với `unit_name` trong Bản đặc tả) |
| `questions` | array | ✅ | Danh sách câu hỏi thuộc đơn vị này |

### Cấp độ Question
| Trường | Kiểu | Bắt buộc | Giá trị cho phép | Mô tả |
|--------|------|----------|------------------|-------|
| `question` | string | ✅ | - | Nội dung câu hỏi |
| `options` | array | ✅ (trừ essay) | - | Danh sách các đáp án (2-6 đáp án) |
| `correct` | number | ✅ (trừ essay) | 0, 1, 2, 3, ... | Index của đáp án đúng (bắt đầu từ 0) |
| `type` | string | ✅ | `single`, `true_false`, `essay` | Loại câu hỏi |
| `level` | string | ✅ | `NB`, `TH`, `VD`, `VDC` | Mức độ nhận thức |

## Loại câu hỏi

### 1. Trắc nghiệm (TNKQ)
```json
{
  "question": "Máy tính thế hệ thứ nhất sử dụng công nghệ gì?",
  "options": [
    "Bóng chân không",
    "Transistor",
    "Mạch tích hợp",
    "Vi xử lý"
  ],
  "correct": 0,
  "type": "single",
  "level": "NB"
}
```

### 2. Đúng/Sai (DS)
```json
{
  "question": "Máy tính thế hệ thứ hai sử dụng transistor.",
  "options": ["Đúng", "Sai"],
  "correct": 0,
  "type": "true_false",
  "level": "TH"
}
```

### 3. Tự luận (TL)
```json
{
  "question": "Phân tích sự phát triển của máy tính qua các thế hệ và tác động của nó đến xã hội.",
  "type": "essay",
  "level": "VD",
  "points": 2.0
}
```

## Mức độ nhận thức

| Mã | Tên | Mô tả |
|----|-----|-------|
| `NB` | Nhận biết | Nhớ, nhận ra thông tin cơ bản |
| `TH` | Thông hiểu | Hiểu, giải thích ý nghĩa |
| `VD` | Vận dụng | Áp dụng kiến thức vào tình huống mới |
| `VDC` | Vận dụng cao | Phân tích, đánh giá, sáng tạo |

## Mapping với Bản đặc tả kỹ thuật

### Quan trọng
- `topic_name` phải **khớp chính xác** với trường `content` trong Bản đặc tả
- `unit_name` phải **khớp chính xác** với trường `unit_name` trong Bản đặc tả

### Ví dụ từ Bản đặc tả:
```json
{
  "content": "Chủ đề F. Giải quyết vấn đề với sự trợ giúp của máy tính",
  "units": [
    {
      "unit_name": "Lập trình trực quan",
      "nhan_biet": "– Nêu được khái niệm hằng, biến...",
      "thong_hieu": "– Hiểu được chương trình là dãy các lệnh...",
      "van_dung": "– Sử dụng được các khái niệm..."
    }
  ]
}
```

### Câu hỏi tương ứng:
```json
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
```

## Quy tắc đặt tên file

### Cấu trúc thư mục
```
teacher/questions/
├── khoi6/
│   ├── hk1/
│   │   ├── subject_1.json
│   │   └── subject_2.json
│   └── hk2/
│       ├── subject_1.json
│       └── subject_2.json
├── khoi7/
├── khoi8/
└── khoi9/
```

### Quy ước đặt tên
- Format: `subject_{subject_id}.json`
- `subject_id`: ID môn học (từ admin/subjects.json)
- Ví dụ: `subject_1.json` (Tin học), `subject_2.json` (Toán)

## Validation

### Kiểm tra khi import
1. ✅ `topic_name` và `unit_name` không được rỗng
2. ✅ `questions` phải là array và có ít nhất 1 câu hỏi
3. ✅ Mỗi câu hỏi phải có đầy đủ các trường bắt buộc
4. ✅ `type` phải thuộc: `single`, `true_false`, `essay`
5. ✅ `level` phải thuộc: `NB`, `TH`, `VD`, `VDC`
6. ✅ `correct` phải < số lượng `options`
7. ✅ TNKQ và DS phải có `options` và `correct`
8. ✅ Essay không cần `options` và `correct`

## Ví dụ đầy đủ

```json
[
  {
    "topic_name": "Chủ đề A. Máy tính và cộng đồng",
    "unit_name": "Sơ lược về lịch sử phát triển máy tính",
    "questions": [
      {
        "question": "Máy tính thế hệ thứ nhất sử dụng công nghệ gì?",
        "options": [
          "Bóng chân không",
          "Transistor",
          "Mạch tích hợp",
          "Vi xử lý"
        ],
        "correct": 0,
        "type": "single",
        "level": "NB"
      },
      {
        "question": "Máy tính ENIAC thuộc thế hệ nào?",
        "options": [
          "Thế hệ 1",
          "Thế hệ 2",
          "Thế hệ 3",
          "Thế hệ 4"
        ],
        "correct": 0,
        "type": "single",
        "level": "TH"
      }
    ]
  },
  {
    "topic_name": "Chủ đề F. Giải quyết vấn đề với sự trợ giúp của máy tính",
    "unit_name": "Lập trình trực quan",
    "questions": [
      {
        "question": "Khái niệm nào sau đây là biến?",
        "options": ["Pi = 3.14", "x = 5", "3.14", "True"],
        "correct": 1,
        "type": "single",
        "level": "NB"
      },
      {
        "question": "Chương trình là tập hợp các lệnh điều khiển máy tính.",
        "options": ["Đúng", "Sai"],
        "correct": 0,
        "type": "true_false",
        "level": "TH"
      },
      {
        "question": "Viết chương trình đơn giản tính tổng hai số trong môi trường lập trình trực quan.",
        "type": "essay",
        "level": "VD",
        "points": 2.0
      }
    ]
  }
]
```

## Migration từ format cũ

### Format cũ (DEPRECATED)
```json
{
  "topic": "Chủ đề 1. Máy tính và cộng đồng",
  "lesson": "Bài 1: Lịch sử phát triển máy tính",
  "questions": [...]
}
```

### Format mới (RECOMMENDED)
```json
{
  "topic_name": "Chủ đề A. Máy tính và cộng đồng",
  "unit_name": "Sơ lược về lịch sử phát triển máy tính",
  "questions": [...]
}
```

### Script migration
```php
// Convert old format to new format
$oldData = json_decode(file_get_contents('old_format.json'), true);
$newData = [];

foreach ($oldData as $item) {
    $newData[] = [
        'topic_name' => $item['topic'] ?? '',
        'unit_name' => $item['lesson'] ?? '',
        'questions' => $item['questions'] ?? []
    ];
}

file_put_contents('new_format.json', json_encode($newData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
```

---

**Lưu ý**: Format mới được áp dụng từ version 5.0 trở đi. Hệ thống vẫn tương thích với format cũ nhưng khuyến nghị migrate sang format mới để mapping chính xác với Bản đặc tả kỹ thuật.
