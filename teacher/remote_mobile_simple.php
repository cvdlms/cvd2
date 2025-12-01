<?php
// Simple mobile remote control
// Usage: remote_mobile_simple.php?session=SESSION_ID

$session_id = $_GET['session'] ?? '';
if (empty($session_id)) {
	http_response_code(400);
	echo 'Missing session parameter';
	exit;
}
?>
<!doctype html>
<html lang="vi">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Remote Mobile - Simple</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
	<style>
		body { background:#f8f9fa; }
		.control-btn { height:70px; font-size:1.1rem; }
		.status { position: fixed; bottom: 12px; left: 12px; right: 12px; }
	</style>
</head>
<body>
<div class="container py-4">
	<div class="text-center mb-3">
		<h4>Điều Khiển Từ Xa (Simple)</h4>
		<p class="text-muted">Session: <strong><?php echo htmlspecialchars($session_id); ?></strong></p>
	</div>

	<div class="row g-2">
		<div class="col-6">
			<button class="btn btn-primary w-100 control-btn" onclick="sendCommand('prev_slide')">
				<i class="bi bi-chevron-left"></i> Trước
			</button>
		</div>
		<div class="col-6">
			<button class="btn btn-primary w-100 control-btn" onclick="sendCommand('next_slide')">
				<i class="bi bi-chevron-right"></i> Sau
			</button>
		</div>
	</div>

	<div class="status mt-3">
		<div id="statusMessage" class="alert alert-secondary text-center mb-0">Sẵn sàng</div>
	</div>
</div>

<script>
const sessionId = <?php echo json_encode($session_id); ?>;

function sendCommand(cmd) {
	const status = document.getElementById('statusMessage');
	status.className = 'alert alert-warning text-center';
	status.textContent = 'Đang gửi lệnh...';

	fetch('api/remote_commands.php', {
		method: 'POST',
		headers: { 'Content-Type': 'application/json' },
		body: JSON.stringify({ session: sessionId, command: cmd })
	})
	.then(r => r.json())
	.then(data => {
		if (data.success) {
			status.className = 'alert alert-success text-center';
			status.textContent = 'Đã gửi: ' + cmd;
			setTimeout(() => { status.className = 'alert alert-secondary text-center'; status.textContent = 'Sẵn sàng'; }, 1500);
		} else {
			status.className = 'alert alert-danger text-center';
			status.textContent = 'Lỗi: ' + (data.message || 'Không gửi được');
		}
	})
	.catch(err => {
		console.error(err);
		status.className = 'alert alert-danger text-center';
		status.textContent = 'Lỗi kết nối';
	});
}
</script>
</body>
</html>
