# 2026-06-13 - Root Login UI

## Muc tieu

- Thay landing page dai tai `/cvdlms/` bang cong dang nhap truc tiep.
- Su dung phong cach tu prototype `cvdlms-v2-html/login.html`.
- Giu nguyen bo xu ly dang nhap legacy hien co.

## Da lam

- Them `index.php`; Apache uu tien file nay truoc `index.html`.
- Tao giao dien dang nhap responsive theo phong cach UI prototype.
- Them tab chon vai tro:
  - Quan tri vien -> `login.php`
  - Giao vien -> `login.php`
  - Hoc sinh -> `student/login.php`
- Khi chon Hoc sinh, field dang nhap doi thanh `student_code`.
- Them nut hien/an mat khau.
- Khong dung so lieu thong ke gia lap tu landing page cu.
- Khong sua `index.html` de giu ban landing page cu lam doi chieu.
- Sua `login.php` de:
  - Xu ly form dang nhap moi ngay ca khi trinh duyet dang giu session admin/giao vien cu.
  - Xoa quyen cua session cu truoc khi xac thuc mot lan dang nhap moi.
  - Doi chieu vai tro da chon voi vai tro that cua tai khoan.
  - Khong cho tai khoan admin dang nhap qua tab Giao vien va nguoc lai.

## Pham vi an toan

- Khong thay doi logic xac thuc.
- Khong thay doi session cua giao vien/admin va hoc sinh.
- Khong thay doi du lieu JSON.

## Kiem tra da chay

```text
C:\xampp\php\php.exe -l index.php
GET http://localhost:8080/cvdlms/ -> 200 OK
GET http://localhost:8080/cvdlms/?role=teacher -> login.php + username
GET http://localhost:8080/cvdlms/?role=student -> student/login.php + student_code
```

Ket qua: thanh cong.

Trinh duyet tich hop khong co phien kha dung trong lan kiem tra nay. Da xac minh output HTML thuc te qua Apache, route form va PHP syntax.
