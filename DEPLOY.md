# QUY TRÌNH DEPLOY AN TOÀN — CVDLMS

> Áp dụng từ commit `c72f639` trở đi: dữ liệu runtime (tài khoản, điểm, bài luyện,
> log đăng nhập, key premium) **không còn nằm trong git**. Hosting tự quản lý dữ liệu,
> git chỉ đồng bộ **code**.

---

## ⚠️ LẦN PULL ĐẦU TIÊN SAU COMMIT `c72f639` — BẮT BUỘC BACKUP

Commit này **xóa** các file dữ liệu khỏi repo. Khi hosting `git pull`, git sẽ
**xóa luôn các file đó khỏi thư mục hosting**. Nếu không backup trước, toàn bộ
tài khoản giáo viên/admin, điểm số, bài luyện tập của học sinh sẽ mất.

### Các file sẽ bị xóa trên hosting khi pull lần này:

```
admin/classes.json
admin/login_attempts.json
admin/premium_keys.json
admin/premium_orders.json
admin/premium_subscriptions.json
admin/student_login_attempts.json
admin/student_practice_history.json
admin/students.json
admin/subjects.json
admin/teacher_classes.json
admin/teacher_subjects.json
admin/user.json
shared/practices/*.json        (toàn bộ, giữ lại .gitkeep)
shared/scores/student_score.json
```

### Các bước (chạy trên hosting, qua SSH/Terminal):

```bash
cd /path/to/cvdlms

# 1. Backup toàn bộ dữ liệu JSON ra ngoài thư mục web
mkdir -p ~/cvdlms_data_backup_$(date +%Y%m%d)
cp -v admin/*.json                       ~/cvdlms_data_backup_$(date +%Y%m%d)/admin/
cp -rv shared/practices/*.json           ~/cvdlms_data_backup_$(date +%Y%m%d)/practices/
cp -v shared/scores/*.json               ~/cvdlms_data_backup_$(date +%Y%m%d)/scores/
cp -v includes/sso_config.php            ~/cvdlms_data_backup_$(date +%Y%m%d)/ 2>/dev/null

# KIỂM TRA: phải thấy file bên trong thư mục backup trước khi làm bước tiếp theo!
ls -la ~/cvdlms_data_backup_$(date +%Y%m%d)/admin/

# 2. Pull code (các file data sẽ bị xóa khỏi working tree)
git pull

# 3. Phục hồi dữ liệu
cp -v ~/cvdlms_data_backup_$(date +%Y%m%d)/admin/*.json   admin/
cp -v ~/cvdlms_data_backup_$(date +%Y%m%d)/practices/*.json shared/practices/
cp -v ~/cvdlms_data_backup_$(date +%Y%m%d)/scores/*.json    shared/scores/

# 4. Kiểm tra nhanh hệ thống
ls admin/user.json shared/scores/student_score.json && echo "OK - du lieu da phuc hoi"
```

> Sau lần này, các file đã được `.gitignore` bảo vệ — những lần pull sau
> **không bao giờ** đụng vào dữ liệu nữa, chỉ cần pull bình thường.

---

## ✅ QUY TRÌNH CHUẨN CHO MỖI LẦN CẬP NHẬT (từ lần thứ 2 trở đi)

### Trên máy local:

```powershell
# 1. Kiểm tra không có file dữ liệu nào lọt vào commit
git status --porcelain
# → Chỉ được chứa .php / .css / .js ... KHÔNG được có *.json trong admin/, shared/, teacher/questions/

git add <cac-file-code>
git commit -m "Mo ta thay doi"
git push
```

### Trên hosting:

```bash
cd /path/to/cvdlms
git pull
```

Xong. Không cần backup vì git không còn quản lý dữ liệu.

---

## 📋 DANH SÁCH DỮ LIỆU ĐƯỢC BẢO VỆ BỞI .gitignore

| Nhóm | File / thư mục |
|---|---|
| Tài khoản & phân công | `admin/user.json`, `students.json`, `classes.json`, `subjects.json`, `teacher_classes.json`, `teacher_subjects.json` |
| Log đăng nhập | `admin/login_attempts.json`, `admin/student_login_attempts.json` |
| Premium | `admin/premium_keys.json`, `premium_orders.json`, `premium_subscriptions.json` |
| Ngân hàng câu hỏi | `teacher/questions/` |
| Đề thi | `teacher/exams/` |
| Điểm & luyện tập | `shared/scores/*`, `shared/practices/*` |
| Upload học sinh | `uploads/**`, `data/**` |
| Backup cục bộ | `*.bak`, `*.bak_*`, `backup*/`, `*.sql`, `*.db` |
| Cấu hình nhạy cảm | `.env`, `includes/sso_config.php` |

## 🧰 LỆNH HỮU ÍCH

```bash
# Xem file nào sắp bị đưa lên git trước khi add
git status --porcelain

# Kiểm tra 1 file có bị ignore hay không
git check-ignore -v admin/user.json

# Tìm xem còn file JSON dữ liệu nào bị track sót không
git ls-files | grep -E "\.(json)$"
# → Chấp nhận được nếu chỉ thấy: composer.json, package.json và các file cấu hình mẫu

# Khắc phục thảm họa: lỡ commit dữ liệu (CHƯA push)
git reset --hard HEAD~1

# Khắc phục: lỡ push dữ liệu lên remote public
# → dùng git filter-repo xóa khỏi lịch sử, sau đó đổi mật khẩu/tài khoản bị lộ
```

---
*Cập nhật: 08/2026 · Áp dụng cho repo CVDLMS nhánh `main`*
