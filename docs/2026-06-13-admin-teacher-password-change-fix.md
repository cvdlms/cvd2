# 2026-06-13 - Admin/Teacher Password Change Fix

## Nguyen nhan

- Admin va giao vien dang nhap bang session `CVD_TEACHER_SESSION`.
- `change_password.php` lai mo session mac dinh `PHPSESSID`.
- Trang doi mat khau khong doc duoc `username` da dang nhap va chuyen nguoi dung ve trang login.

## Da sua

- Dat `session_name('CVD_TEACHER_SESSION')` truoc `session_start()`.
- Xac dinh trang quay lai bang role trong session.
- Kiem tra do dai mat khau theo `admin/system_config.json`.
- Khong cho mat khau moi trung mat khau hien tai.
- Kiem tra JSON tai khoan truoc khi cap nhat.
- Ghi `admin/user.json` bang `LOCK_EX`.
- Xoa session va cookie sau khi doi mat khau thanh cong, bat buoc dang nhap lai.

## Pham vi

- Ap dung cho ca admin va giao vien.
- Khong thay doi mat khau hoc sinh.
- Khong thay doi du lieu tai khoan trong qua trinh kiem thu.

## Kiem tra

- `change_password.php` khong co loi cu phap PHP.
- Session admin gia lap mo duoc form va co link ve `admin/dashboard.php`.
- Session giao vien gia lap mo duoc form va co link ve `teacher/teacher.php`.
- `admin/user.json` doc duoc 19 tai khoan; tat ca password deu la hash hop le.
- Khong gui POST doi mat khau trong test de khong thay doi tai khoan that.

Ket qua: thanh cong.
