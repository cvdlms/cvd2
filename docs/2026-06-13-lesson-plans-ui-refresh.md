# 2026-06-13 - Lesson Plans UI Refresh

## Muc tieu

- Cai thien giao dien `teacher/lesson_plans.php` theo huong chuyen nghiep, de dung cho giao vien.
- Giu nguyen nghiep vu KHBD, API va cau truc du lieu hien tai.

## Da lam

- Doi tieu de thanh `Ke hoach bai day (KHBD)` va xac dinh ro day la ho so chuyen mon.
- Them 4 chi so tong quan lay tu du lieu that:
  - Tong KHBD cua giao vien.
  - KHBD sap den ngay day trong 7 ngay.
  - KHBD trong thang.
  - KHBD dang chia se.
- Tao thanh tim kiem va bo loc theo:
  - Ten bai day/tiet PPCT.
  - Mon hoc.
  - Lop hoc.
  - Ngay day.
  - KHBD cua toi/dang chia se/duoc chia se.
- Bo sung reset filter va empty/error state.
- Cai thien bang danh sach, badge trang thai va nhom nut thao tac.
- Chi hien nut sua/xoa cho KHBD do giao vien hien tai so huu.
- Cai thien modal tao/sua, wizard va 4 panel hoat dong de dong bo giao dien.
- Cai thien modal xem KHBD theo dang trang ho so:
  - Font noi dung 16px, line-height 1.75.
  - Trang tai lieu can giua tren nen xam nhat.
  - Tieu de va metadata ro cap bac.
  - Muc tieu, thiet bi, hoat dong va huong dan ve nha co khoang cach de doc.
  - Responsive cho man hinh nho.
- Them responsive cho desktop, tablet va dien thoai.
- Tach CSS rieng tai `teacher/assets/lesson_plans.css`.

## Khong thay doi

- API `teacher/api/lesson_plans_api.php`.
- Cau truc JSON KHBD.
- Modal field va payload tao/sua.
- Chuc nang xem, xuat Word, xuat PDF, xoa.

## Kiem tra

- PHP syntax: thanh cong.
- Render qua Apache voi session giao vien Premium: HTTP 200.
- Khong co PHP warning/fatal error.
- Hai script inline deu qua `node --check`.
