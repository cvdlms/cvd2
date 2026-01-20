# 🏫 ROADMAP: CHUYỂN ĐỔI HỆ THỐNG CVD THÀNH MULTI-SCHOOL PLATFORM

> **Tài liệu kế hoạch chi tiết** - Chuẩn bị sẵn sàng để triển khai khi cần
> 
> Ngày tạo: 19/01/2026
> Trạng thái: **ĐANG CHỜ TRIỂN KHAI**

---

## 📋 MỤC LỤC
1. [Tổng quan kiến trúc](#tổng-quan-kiến-trúc)
2. [Cấu trúc dữ liệu](#cấu-trúc-dữ-liệu)
3. [Kế hoạch triển khai 8 tuần](#kế-hoạch-triển-khai)
4. [Chi tiết kỹ thuật](#chi-tiết-kỹ-thuật)
5. [Checklist triển khai](#checklist-triển-khai)

---

## 🎯 TỔNG QUAN KIẾN TRÚC

### Mục tiêu
Cho phép nhiều trường học sử dụng chung 1 hệ thống CVD, mỗi trường có:
- ✅ Dữ liệu riêng biệt (exams, questions, users, results)
- ✅ Branding riêng (logo, tên trường, màu sắc)
- ✅ Quản lý độc lập (admin per school)
- ✅ Bảo mật tuyệt đối (không trường nào xem được data trường khác)

### Lựa chọn kiến trúc: **MULTI-TENANT với JSON Database**

**Lý do:**
- Giữ đơn giản, không cần MySQL ngay
- Chi phí thấp, dễ deploy
- Dễ migrate sang database thật sau
- Phù hợp với quy mô nhỏ-vừa (< 50 trường)

**Nguyên tắc:**
- Mọi dữ liệu phải có `school_id`
- Middleware kiểm tra school context cho mọi request
- Data isolation hoàn toàn
- 1 Super Admin quản lý tất cả

---

## 📊 CẤU TRÚC DỮ LIỆU

### Thư mục mới (so với hiện tại)

```
cvd2/
├── data/
│   ├── global/                      # [MỚI] Dữ liệu toàn hệ thống
│   │   ├── schools.json             # Danh sách trường
│   │   ├── superadmin_users.json    # Super admin accounts
│   │   └── system_config.json       # Cấu hình hệ thống
│   │
│   └── schools/                     # [MỚI] Dữ liệu từng trường
│       ├── school_001/              # Trường đầu tiên (migrate từ data hiện tại)
│       │   ├── config.json          # Cấu hình trường
│       │   ├── users.json           # Teachers + Students + Admin
│       │   ├── exams/               # Đề thi
│       │   ├── questions/           # Ngân hàng câu hỏi
│       │   ├── results/             # Kết quả
│       │   └── uploads/             # Files upload
│       │
│       ├── school_002/              # Trường thứ 2
│       │   └── ... (cấu trúc giống school_001)
│       │
│       └── school_xxx/              # Trường thứ n
│
├── includes/
│   ├── school_context.php           # [MỚI] Xác định school hiện tại
│   ├── multi_tenant.php             # [MỚI] Helper functions
│   └── session_check.php            # [SỬA] Thêm school_id validation
│
├── superadmin/                      # [MỚI] Quản lý toàn hệ thống
│   ├── index.php                    # Dashboard tổng quan
│   ├── manage_schools.php           # CRUD schools
│   ├── create_school.php            # Wizard tạo trường mới
│   ├── school_stats.php             # Thống kê từng trường
│   └── system_settings.php          # Cấu hình hệ thống
│
└── setup/                           # [MỚI] Scripts setup ban đầu
    ├── migrate_to_multischool.php   # Migration script
    └── create_default_school.php    # Tạo trường mặc định
```

---

## 🗂️ SCHEMA DỮ LIỆU CHI TIẾT

### 1. `data/global/schools.json`
```json
{
  "school_001": {
    "school_id": "school_001",
    "name": "THCS Nguyễn Trãi",
    "code": "NT-HCM",
    "domain": "nguyentrai.cvd.edu.vn",
    "logo": "schools/school_001/logo.png",
    "address": "123 Nguyễn Trãi, Q.1, TP.HCM",
    "phone": "028-1234567",
    "email": "contact@nguyentrai.edu.vn",
    "status": "active",
    "created_at": "2026-01-20",
    "settings": {
      "theme_color": "#4472C4",
      "allow_student_register": false,
      "max_teachers": 50,
      "max_students": 1000
    }
  },
  "school_002": {
    "school_id": "school_002",
    "name": "THCS Lê Lợi",
    "code": "LL-HN",
    "domain": "leloi.cvd.edu.vn",
    "status": "active",
    "created_at": "2026-02-15",
    "settings": { ... }
  }
}
```

### 2. `data/schools/{school_id}/config.json`
```json
{
  "school_id": "school_001",
  "admin_users": ["admin_nt", "hieutruong_nguyen"],
  "subjects": [
    {"id": "tin_hoc_6", "name": "Tin học 6", "grades": [6]},
    {"id": "tin_hoc_7", "name": "Tin học 7", "grades": [7]},
    {"id": "tin_hoc_8", "name": "Tin học 8", "grades": [8]},
    {"id": "tin_hoc_9", "name": "Tin học 9", "grades": [9]}
  ],
  "school_year": "2025-2026",
  "semester": 2,
  "branding": {
    "primary_color": "#4472C4",
    "logo_url": "/data/schools/school_001/logo.png",
    "header_text": "Trường THCS Nguyễn Trãi"
  }
}
```

### 3. `data/schools/{school_id}/users.json`
```json
{
  "teacher_nguyen": {
    "username": "teacher_nguyen",
    "password": "hashed_password",
    "fullname": "Nguyễn Văn A",
    "role": "teacher",
    "school_id": "school_001",
    "email": "nguyenvana@school001.edu.vn",
    "subjects": ["tin_hoc_8", "tin_hoc_9"],
    "created_at": "2026-01-20"
  },
  "student_001": {
    "username": "student_001",
    "password": "hashed_password",
    "fullname": "Trần Thị B",
    "role": "student",
    "school_id": "school_001",
    "class": "8A1",
    "student_code": "001",
    "created_at": "2026-01-20"
  },
  "admin_nt": {
    "username": "admin_nt",
    "password": "hashed_password",
    "fullname": "Hiệu trưởng Nguyễn",
    "role": "admin",
    "school_id": "school_001",
    "is_school_admin": true,
    "created_at": "2026-01-20"
  }
}
```

### 4. `data/global/superadmin_users.json`
```json
{
  "superadmin": {
    "username": "superadmin",
    "password": "hashed_super_password",
    "fullname": "System Administrator",
    "role": "superadmin",
    "email": "admin@cvd.edu.vn",
    "can_create_schools": true,
    "can_delete_schools": true,
    "can_view_all_data": false,
    "created_at": "2026-01-01"
  }
}
```

---

## 🔧 CHI TIẾT KỸ THUẬT

### File 1: `includes/school_context.php`

```php
<?php
/**
 * School Context Management
 * Xác định trường học hiện tại từ session hoặc domain
 */

class SchoolContext {
    private static $current_school_id = null;
    
    /**
     * Initialize school context
     * Call this in session_check.php
     */
    public static function init() {
        // Option 1: Từ session (sau khi login)
        if (isset($_SESSION['school_id'])) {
            self::$current_school_id = $_SESSION['school_id'];
            return;
        }
        
        // Option 2: Từ subdomain (nếu có)
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (preg_match('/^([a-z0-9-]+)\.cvd\.edu\.vn$/i', $host, $matches)) {
            $subdomain = $matches[1];
            $school_id = self::getSchoolIdBySubdomain($subdomain);
            if ($school_id) {
                self::$current_school_id = $school_id;
                $_SESSION['school_id'] = $school_id;
                return;
            }
        }
        
        // Option 3: Default school (fallback)
        self::$current_school_id = 'school_001';
    }
    
    /**
     * Get current school ID
     */
    public static function getCurrentSchoolId() {
        if (!self::$current_school_id) {
            self::init();
        }
        return self::$current_school_id;
    }
    
    /**
     * Get school data path
     */
    public static function getSchoolDataPath($subpath = '') {
        $school_id = self::getCurrentSchoolId();
        $base = __DIR__ . "/../data/schools/{$school_id}";
        return $subpath ? "{$base}/{$subpath}" : $base;
    }
    
    /**
     * Validate user belongs to current school
     */
    public static function validateUserSchool($username) {
        $school_id = self::getCurrentSchoolId();
        $users_file = self::getSchoolDataPath('users.json');
        
        if (!file_exists($users_file)) {
            return false;
        }
        
        $users = json_decode(file_get_contents($users_file), true);
        return isset($users[$username]) && $users[$username]['school_id'] === $school_id;
    }
    
    /**
     * Get school config
     */
    public static function getSchoolConfig() {
        $config_file = self::getSchoolDataPath('config.json');
        if (file_exists($config_file)) {
            return json_decode(file_get_contents($config_file), true);
        }
        return null;
    }
    
    /**
     * Get school info from global registry
     */
    public static function getSchoolInfo($school_id = null) {
        $school_id = $school_id ?? self::getCurrentSchoolId();
        $schools_file = __DIR__ . '/../data/global/schools.json';
        
        if (!file_exists($schools_file)) {
            return null;
        }
        
        $schools = json_decode(file_get_contents($schools_file), true);
        return $schools[$school_id] ?? null;
    }
    
    private static function getSchoolIdBySubdomain($subdomain) {
        $schools_file = __DIR__ . '/../data/global/schools.json';
        if (!file_exists($schools_file)) {
            return null;
        }
        
        $schools = json_decode(file_get_contents($schools_file), true);
        foreach ($schools as $id => $school) {
            if (isset($school['domain']) && strpos($school['domain'], $subdomain) === 0) {
                return $id;
            }
        }
        return null;
    }
}
```

### File 2: `includes/multi_tenant.php`

```php
<?php
/**
 * Multi-tenant Helper Functions
 */

require_once __DIR__ . '/school_context.php';

/**
 * Load users from current school
 */
function load_school_users() {
    $users_file = SchoolContext::getSchoolDataPath('users.json');
    if (!file_exists($users_file)) {
        return [];
    }
    return json_decode(file_get_contents($users_file), true) ?? [];
}

/**
 * Save users to current school
 */
function save_school_users($users) {
    $users_file = SchoolContext::getSchoolDataPath('users.json');
    $dir = dirname($users_file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * Load exams from current school
 */
function load_school_exams() {
    $exams_dir = SchoolContext::getSchoolDataPath('exams');
    if (!is_dir($exams_dir)) {
        return [];
    }
    
    $exams = [];
    foreach (glob("{$exams_dir}/*.json") as $file) {
        $exam_id = basename($file, '.json');
        $exams[$exam_id] = json_decode(file_get_contents($file), true);
    }
    return $exams;
}

/**
 * Get school-specific path for exams
 */
function get_school_exam_path($exam_id) {
    return SchoolContext::getSchoolDataPath("exams/{$exam_id}.json");
}

/**
 * Get school-specific path for questions
 */
function get_school_questions_path($subject_id = '') {
    if ($subject_id) {
        return SchoolContext::getSchoolDataPath("questions/{$subject_id}.json");
    }
    return SchoolContext::getSchoolDataPath("questions");
}

/**
 * Check if user is school admin
 */
function is_school_admin($username) {
    $config = SchoolContext::getSchoolConfig();
    if (!$config) return false;
    return in_array($username, $config['admin_users'] ?? []);
}

/**
 * Check if user is super admin
 */
function is_super_admin($username) {
    $superadmin_file = __DIR__ . '/../data/global/superadmin_users.json';
    if (!file_exists($superadmin_file)) {
        return false;
    }
    $superadmins = json_decode(file_get_contents($superadmin_file), true);
    return isset($superadmins[$username]) && $superadmins[$username]['role'] === 'superadmin';
}
```

### File 3: Cập nhật `includes/session_check.php`

```php
<?php
// Thêm vào đầu file hiện tại
require_once __DIR__ . '/school_context.php';

session_start();

// Initialize school context
SchoolContext::init();

// Existing session checks...
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit;
}

// NEW: Validate user belongs to current school
if (!SchoolContext::validateUserSchool($_SESSION['username'])) {
    session_destroy();
    header('Location: ../login.php?error=invalid_school');
    exit;
}

// Store school info in session for easy access
$_SESSION['school_info'] = SchoolContext::getSchoolInfo();
$_SESSION['school_config'] = SchoolContext::getSchoolConfig();
```

---

## 📅 KẾ HOẠCH TRIỂN KHAI 8 TUẦN

### **TUẦN 1: Chuẩn bị & Thiết kế**
- [ ] Backup toàn bộ hệ thống hiện tại
- [ ] Tạo git branch `multi-tenant`
- [ ] Review và finalize database schema
- [ ] Tạo mockup giao diện Super Admin
- [ ] Viết test plan chi tiết

**Deliverables:**
- Full backup file
- Database schema document
- UI mockups
- Test scenarios

---

### **TUẦN 2: Core Infrastructure (Phần 1)**
- [ ] Tạo cấu trúc thư mục mới:
  - `data/global/`
  - `data/schools/`
- [ ] Tạo `includes/school_context.php`
- [ ] Tạo `includes/multi_tenant.php`
- [ ] Tạo `data/global/schools.json` với school mặc định

**Deliverables:**
- School context system hoạt động
- Helper functions cơ bản

---

### **TUẦN 3: Core Infrastructure (Phần 2)**
- [ ] Cập nhật `includes/session_check.php`
- [ ] Implement school validation middleware
- [ ] Tạo utility functions:
  - `load_school_users()`
  - `save_school_users()`
  - `get_school_exam_path()`
- [ ] Unit testing cho core functions

**Deliverables:**
- Session management với school context
- Tested helper functions

---

### **TUẦN 4: Data Migration**
- [ ] Tạo script `setup/migrate_to_multischool.php`
- [ ] Migrate data hiện tại sang `school_001`:
  - `admin/user.json` → `schools/school_001/users.json`
  - `questions/` → `schools/school_001/questions/`
  - `data/results/` → `schools/school_001/results/`
- [ ] Verify data integrity sau migration
- [ ] Rollback plan và testing

**Deliverables:**
- Migration script hoàn chỉnh
- Data đã migrate thành công
- Backup trước và sau migration

---

### **TUẦN 5: Update Login & User Management**
- [ ] Cập nhật `login.php`:
  - Option chọn trường (nếu multi-school login)
  - Hoặc auto-detect từ subdomain
  - Load users từ school-specific file
- [ ] Cập nhật `admin/manage_users.php`:
  - CRUD users trong scope của school
  - Thêm trường `school_id` vào user data
- [ ] Cập nhật `teacher/` và `student/` pages:
  - Load data từ school context

**Deliverables:**
- Login system hỗ trợ multi-school
- User management scoped by school

---

### **TUẦN 6: Update Exam & Question System**
- [ ] Cập nhật `teacher/create_exam.php`:
  - Save exams vào `schools/{school_id}/exams/`
  - Load questions từ school question bank
- [ ] Cập nhật `teacher/manage_questions.php`:
  - CRUD questions trong school scope
- [ ] Cập nhật `student/exam.php`:
  - Load exams từ school data
  - Save results vào school results folder
- [ ] Testing với multiple schools

**Deliverables:**
- Exam system hoàn toàn isolated per school
- Question banks riêng biệt

---

### **TUẦN 7: Super Admin Panel**
- [ ] Tạo `superadmin/index.php` - Dashboard
  - Tổng quan số lượng schools
  - Statistics: users, exams, students per school
- [ ] Tạo `superadmin/manage_schools.php`
  - List all schools
  - View school details
  - Activate/deactivate school
- [ ] Tạo `superadmin/create_school.php`
  - Wizard tạo trường mới
  - Auto-create folder structure
  - Generate default config
  - Tạo admin account đầu tiên
- [ ] Tạo `superadmin/school_stats.php`
  - Detailed stats per school
  - Export reports

**Deliverables:**
- Super Admin panel đầy đủ chức năng
- School creation wizard

---

### **TUẦN 8: Testing, Security & Documentation**
- [ ] Security audit:
  - Test data isolation giữa schools
  - SQL injection prevention
  - XSS protection
  - Access control testing
- [ ] Performance testing:
  - Test với 10-20 schools
  - Load testing
  - Optimize file I/O
- [ ] User acceptance testing:
  - Test với 3 schools thật
  - Gather feedback
- [ ] Documentation:
  - User manual cho Super Admin
  - User manual cho School Admin
  - Developer guide
  - Deployment guide

**Deliverables:**
- Security report
- Performance benchmark
- Complete documentation
- Training materials

---

## ✅ CHECKLIST TRIỂN KHAI

### Pre-deployment
```
□ Backup đầy đủ database và files
□ Git branch multi-tenant đã tested kỹ
□ All tests pass (unit + integration)
□ Documentation hoàn chỉnh
□ Training materials sẵn sàng
□ Rollback plan chi tiết
```

### Deployment Day
```
□ Maintenance mode ON
□ Final backup
□ Run migration script
□ Verify data integrity
□ Create default super admin
□ Create first school (school_001)
□ Test login với super admin
□ Test login với school admin
□ Test basic operations (exam, questions)
□ Maintenance mode OFF
□ Monitor logs trong 24h
```

### Post-deployment
```
□ Train super admin
□ Train school admins
□ Monitor performance
□ Collect feedback
□ Fix issues nhanh
□ Plan for improvements
```

---

## 🔐 BẢO MẬT & BEST PRACTICES

### Security Rules
1. **Mọi query phải filter theo school_id**
2. **Không bao giờ trust user input về school_id**
3. **Session phải bind với school_id**
4. **File permissions: 644 cho files, 755 cho folders**
5. **Audit logs cho mọi thao tác quan trọng**

### Code Standards
```php
// ✅ ĐÚNG - Always use school context
$users = load_school_users(); // Auto scoped to current school

// ❌ SAI - Direct file access
$users = json_decode(file_get_contents('../admin/user.json'), true);

// ✅ ĐÚNG - Explicit school_id check
if (!SchoolContext::validateUserSchool($username)) {
    die('Access denied');
}

// ❌ SAI - No validation
$user_data = $users[$username];
```

---

## 📊 METRICS ĐỂ ĐÁNH GIÁ THÀNH CÔNG

### Technical Metrics
- [ ] 100% data isolation (no cross-school data access)
- [ ] < 200ms response time cho mỗi request
- [ ] Hỗ trợ tối thiểu 50 schools đồng thời
- [ ] 99.9% uptime
- [ ] Zero security breaches

### Business Metrics
- [ ] Tạo school mới trong < 5 phút
- [ ] School admin có thể quản lý hoàn toàn school của mình
- [ ] User satisfaction > 4/5 stars
- [ ] Giảm 80% thời gian support cho multi-school setup

---

## 🚀 FUTURE ENHANCEMENTS

### Phase 2 (Sau khi Multi-School ổn định)
- [ ] Migrate sang MySQL/PostgreSQL database
- [ ] API REST cho mobile apps
- [ ] SSO (Single Sign-On) integration
- [ ] Advanced analytics & reporting
- [ ] Subscription & billing system
- [ ] Email notification system
- [ ] SMS integration
- [ ] Parent portal
- [ ] Cross-school data sharing (with permission)

### Phase 3 (Long-term)
- [ ] Mobile apps (iOS + Android)
- [ ] AI-powered question generation
- [ ] Advanced exam analytics
- [ ] Integration với Google Classroom
- [ ] Blockchain certificates
- [ ] Multi-language support

---

## 📞 LIÊN HỆ & HỖ TRỢ

**Khi bắt đầu triển khai, cần:**
- [ ] Dedicated developer (full-time, 8 tuần)
- [ ] Test server (không deploy trên production ngay)
- [ ] 3-5 schools thử nghiệm
- [ ] Budget cho hosting và domain (nếu dùng subdomain)

**Câu hỏi thường gặp:**
- Q: Có phải migrate sang MySQL không?
  - A: Không bắt buộc, JSON đủ cho < 50 schools. Sau đó mới migrate.

- Q: Subdomain hay single domain?
  - A: Có thể cả 2. Bắt đầu với single domain, sau đó thêm subdomain.

- Q: Chi phí hosting tăng bao nhiêu?
  - A: Minimal nếu < 20 schools. Same server, just organized folders.

---

## 📝 NOTES & REMINDERS

**⚠️ QUAN TRỌNG:**
- Luôn test trên staging environment trước
- Migration script phải có rollback
- Backup trước mỗi bước quan trọng
- Document mọi thay đổi trong migration log

**💡 TIPS:**
- Bắt đầu với 1 school thử nghiệm (school_001)
- Sau khi ổn định, invite 2-3 schools beta test
- Collect feedback trước khi scale
- Plan cho growth, nhưng implement simple first

---

**Status:** 🟡 READY TO IMPLEMENT WHEN NEEDED

**Ước tính effort:**
- Development: 8 tuần (1 developer full-time)
- Testing: 2 tuần
- Deployment & Training: 1 tuần
- **Total: ~11 tuần (3 tháng)**

---

*Document này sẽ được update khi có thay đổi trong requirement hoặc trong quá trình triển khai.*
