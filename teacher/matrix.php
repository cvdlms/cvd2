<?php
session_start();
include '../includes/session_check.php'; // Ensure logged in

// Check if teacher (not admin)
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../login.php');
    exit;
}

// Load user data for fullname
$users = json_decode(file_get_contents('../admin/user.json'), true);
$username = $_SESSION['username'];
$fullname = $users[$username]['fullname'] ?? $username;

$title = 'Ma Trận Đề Kiểm Tra - CVD';
include '../includes/teacher_header.php';
?>
<style>
      
    .table { font-size: 11px; }
    .table thead { background: linear-gradient(135deg, #00b4db 0%, #0083b0 100%); color: white; font-weight: bold; }
    .table-bordered th, .table-bordered td { border: 2px solid #00b4db; padding: 6px 6px; vertical-align: middle; }
    .bg-primary-custom { background: linear-gradient(135deg, #00b4db 0%, #0083b0 100%) !important; color: white !important; }
    .bg-success-light { background-color: #d4edda !important; }
    .text-success { color: #28a745 !important; font-weight: bold; }
    .small-text { font-size: 10px; }
 
</style>
<div class="main-contents">
  <div class="container-fluid">
    <div class="mb-4 text-center">
    <h2>🎓 Ma trận đề kiểm tra Tin học lớp 7</h2>
    <p><strong>Chuẩn yêu cầu:</strong> TNKQ = 8 câu (4.0đ) • Đúng/Sai = 2 câu (2.0đ) • Tự luận = 4 câu (4.0đ).<br>
    Tỉ lệ mức độ: NB = 35%, TH = 35%, VD = 30%. Tổng = 10.0đ.</p>
    </div>


    <div class="table-responsive">
      <table class="table table-bordered table-sm align-middle text-center">
        <thead>
          <tr>
            <th rowspan="3">Chủ đề / Đơn vị kiến thức (tiết)</th>
            <th colspan="3">TNKQ (8c = 4.0đ)</th>
            <th colspan="3">Đúng/Sai (2c = 2.0đ)</th>
            <th colspan="3">Tự luận (4c = 4.0đ)</th>
            <th colspan="3">TỔNG mức độ (đ + số câu/ý)</th>
            <th rowspan="3">Tỉ lệ (%)</th>
          </tr>
          <tr>
            <th>NB</th><th>TH</th><th>VD</th>
            <th>NB</th><th>TH</th><th>VD</th>
            <th>NB</th><th>TH</th><th>VD</th>
            <th class="bg-success-light">NB</th>
            <th class="bg-success-light">TH</th>
            <th class="bg-success-light">VD</th>
          </tr>
        </thead>
        <tbody>
          <!-- Chủ đề A -->
          <tr class="bg-primary-custom"><td colspan="15" class="text-start fw-bold">A. MÁY TÍNH VÀ CỘNG ĐỒNG (5 tiết)</td></tr>
          <tr>
            <td class="text-start ps-3 small-text">1. Sơ lược về các thành phần của máy tính (2 tiết)</td>
            <!-- TNKQ -->
            <td>1c<br>0.5đ</td>
            <td>1c<br>0.5đ</td>
            <td></td>
            <!-- Đ/S -->
            <td>1ý<br>0.25đ</td>
            <td>1ý<br>0.25đ</td>
            <td>2ý<br>0.5đ</td>
            <!-- TL -->
            <td></td>
            <td>1c<br>1đ</td>
            <td><br></td>
            <!-- Tổng -->
            <td class="bg-success-light">0.75đ<br>(1c+1ý)</td>
            <td class="bg-success-light">1.75đ<br>(2c+1ý)</td>
            <td class="bg-success-light">0.5đ<br>(2ý)</td>
            <td class="text-success">30%</td>
          </tr>

          <tr>
            <td class="text-start ps-3 small-text">2. Hệ điều hành và phần mềm ứng dụng (3 tiết)</td>
            <!-- TNKQ -->
            <td>2c<br>1.0đ</td>
            <td>1c<br>0.5đ</td>
            <td></td>
            <!-- Đ/S -->
            <td>1ý<br>0.25đ</td>
            <td>1ý<br>0.25đ</td>
            <td>2ý<br>0.5đ</td>
            <!-- TL -->
            <td>1c<br>0.5đ</td>
            <td></td>
            <td>1c<br>1đ</td>
            <!-- Tổng -->
            <td class="bg-success-light">1.75đ<br>(3c+1ý)</td>
            <td class="bg-success-light">0.75đ<br>(1c+1ý)</td>
            <td class="bg-success-light">1.5đ<br>(1c+2ý)</td>
            <td class="text-success">40%</td>
          </tr>

          <!-- Chủ đề B -->
          <tr class="bg-primary-custom"><td colspan="15" class="text-start fw-bold">B. TỔ CHỨC LƯU TRỮ, TÌM KIẾM VÀ TRAO ĐỔI THÔNG TIN (2 tiết)</td></tr>
          <tr>
            <td class="text-start ps-3 small-text">Mạng xã hội và các kênh trao đổi thông tin (2 tiết)</td>
            <!-- TNKQ -->
            <td>2c<br>1.0đ</td>
            <td>1c<br>0.5đ</td>
            <td></td>
            <!-- Đ/S -->
            <td></td>
            <td></td>
            <td></td>
            <!-- TL -->
            <td></td>
            <td>1c<br>0.5đ</td>
            <td>1c<br>1.0đ</td>
            <!-- Tổng -->
            <td class="bg-success-light">1đ<br>(2c)</td>
            <td class="bg-success-light">1đ<br>(2c)</td>
            <td class="bg-success-light">1đ<br>(1c)</td>
            <td class="text-success">30%</td>
          </tr>

          <!-- Tổng hợp -->
          <tr class="table-warning fw-bold">
            <td class="text-start">TỔNG CỘT</td>
            <td colspan="3">TNKQ: 8 câu = 4.0đ</td>
            <td colspan="3">Đ/S: 2 câu = 2.0đ</td>
            <td colspan="3">Tự luận: 4 câu = 4.0đ</td>
            <td class="bg-info">NB: 3.5đ<br>(6c+2ý)</td>
            <td class="bg-info">TH: 3.5đ<br>(5c+2ý)</td>
            <td class="bg-info">VD: 3.0đ<br>(2c+4ý)</td>
            <td class="text-danger">100%</td>
          </tr>

          <tr class="table-secondary small-text fw-bold">
            <td class="text-start">Ghi chú</td>
            <td colspan="14">
              ✅ Đã chỉnh chuẩn 10.0đ: TNKQ 4.0đ, Đ/S 2.0đ, Tự luận 4.0đ.<br>
              Tổng toàn đề: NB = 3.5đ, TH = 3.5đ, VD = 3.0đ — đúng 35%–35%–30%.<br>
              Không thừa điểm, không lệch ma trận.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
