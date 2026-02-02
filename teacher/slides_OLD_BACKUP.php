<?php
session_name('CVD_TEACHER_SESSION');
session_start();

include '../includes/session_check.php';

// Check if teacher (not admin)
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../login.php');
    exit;
}

$username = $_SESSION['username'];

// Load user data
$users = json_decode(file_get_contents(__DIR__ . '/../admin/user.json'), true);
$fullname = $users[$username]['fullname'] ?? $username;

// Use new storage system
require_once __DIR__ . '/../includes/PresentationStorage.php';
$storage = new PresentationStorage();

// Check if migration needed
if ($storage->isLegacySystem()) {
    echo '<div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 9999; display: flex; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 10px; max-width: 500px;">
            <h2>🔄 Cần Cập Nhật Hệ Thống</h2>
            <p>Hệ thống cần chuyển đổi dữ liệu slide sang định dạng mới để hỗ trợ import PowerPoint.</p>
            <p><strong>Chỉ Admin mới có thể thực hiện migration.</strong></p>
            <a href="migrate_presentations.php" style="display: inline-block; background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Chạy Migration (Admin Only)</a>
        </div>
    </div>';
}

// Load presentations for this teacher
$myPresentations = $storage->getByTeacher($username);

// Load templates from both files (for "Create from Template" feature)
$templates = [];
$templatesFile = __DIR__ . '/../data/slide_templates.json';
$templatesCompleteFile = __DIR__ . '/../data/slide_templates_complete.json';

if (file_exists($templatesFile)) {
    $basicTemplates = json_decode(file_get_contents($templatesFile), true);
    if (is_array($basicTemplates)) {
        $templates = array_merge($templates, $basicTemplates);
    }
}
if (file_exists($templatesCompleteFile)) {
    $completeTemplates = json_decode(file_get_contents($templatesCompleteFile), true);
    if (is_array($completeTemplates)) {
        $templates = array_merge($templates, $completeTemplates);
    }
}

$title = 'Slide Bài Giảng - CVD';
include '../includes/teacher_header.php';
?>

<link rel="stylesheet" href="../styles/slide-system.css">

<div class="slide-library-container">
    <!-- Header -->
    <div class="slide-library-header slide-fade-in">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1><i class="bi bi-easel"></i> Slide Bài Giảng</h1>
                <p>Tạo và quản lý bài giảng trực quan, chuyên nghiệp</p>
            </div>
            <div>
                <span class="slide-badge slide-badge-primary">
                    <?php echo count($myPresentations); ?> bài giảng
                </span>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="slide-toolbar">
        <div class="slide-search-box">
            <i class="bi bi-search"></i>
            <input type="text" id="searchInput" placeholder="Tìm kiếm bài giảng...">
        </div>
        
        <div class="slide-filter-group">
            <select id="filterSubject">
                <option value="">Tất cả môn học</option>
                <option value="1">Toán</option>
                <option value="2">Vật Lý</option>
                <option value="3">Hóa Học</option>
            </select>
            
            <select id="filterSort">
                <option value="newest">Mới nhất</option>
                <option value="oldest">Cũ nhất</option>
                <option value="name">Tên A-Z</option>
                <option value="views">Nhiều lượt xem</option>
            </select>
        </div>
        
        <div class="d-flex gap-2">
            <a href="import_pptx.php" class="slide-btn slide-btn-success">
                <i class="bi bi-cloud-upload"></i>
                Import PowerPoint
            </a>
            <a href="slide_builder.php" class="slide-btn slide-btn-primary">
                <i class="bi bi-palette"></i>
                Tạo Từ Template
            </a>
        </div>
    </div>

    <!-- Presentations Grid -->
    <?php if (empty($myPresentations)): ?>
        <div class="slide-empty-state">
            <div class="slide-empty-icon">
                <i class="bi bi-easel"></i>
            </div>
            <h3>Chưa có bài giảng nào</h3>
            <p>Bắt đầu tạo bài giảng đầu tiên của bạn hoặc chọn từ template có sẵn</p>
            <a href="slide_builder.php" class="slide-btn slide-btn-primary">
                <i class="bi bi-plus-circle"></i>
                Tạo Bài Giảng Mới
            </a>
        </div>
    <?php else: ?>
        <div class="slide-grid" id="presentationsGrid">
            <?php foreach ($myPresentations as $pres): ?>
                <?php
                $slideCount = count($pres['slides'] ?? []);
                $views = $pres['statistics']['total_views'] ?? 0;
                $duration = isset($pres['statistics']['avg_time_spent']) ? 
                    round($pres['statistics']['avg_time_spent'] / 60) : 0;
                $updatedDate = new DateTime($pres['updated_at']);
                $now = new DateTime();
                $diff = $now->getTimestamp() - $updatedDate->getTimestamp();
                
                if ($diff < 3600) {
                    $timeAgo = floor($diff / 60) . ' phút trước';
                } elseif ($diff < 86400) {
                    $timeAgo = floor($diff / 3600) . ' giờ trước';
                } elseif ($diff < 604800) {
                    $timeAgo = floor($diff / 86400) . ' ngày trước';
                } else {
                    $timeAgo = $updatedDate->format('d/m/Y');
                }
                ?>
                
                <div class="slide-card" data-id="<?php echo $pres['id']; ?>">
                    <div class="slide-card-thumbnail">
                        <?php if (!empty($pres['thumbnail'])): ?>
                            <img src="../<?php echo htmlspecialchars($pres['thumbnail']); ?>" alt="Thumbnail">
                        <?php else: ?>
                            <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: white; font-size: 3rem;">
                                <i class="bi bi-easel"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="slide-card-overlay">
                            <div class="slide-card-quick-actions">
                                <button class="slide-card-quick-btn" onclick="presentSlide('<?php echo $pres['id']; ?>')">
                                    <i class="bi bi-play-fill"></i> Trình chiếu
                                </button>
                                <button class="slide-card-quick-btn" onclick="editSlide('<?php echo $pres['id']; ?>')">
                                    <i class="bi bi-pencil"></i> Chỉnh sửa
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="slide-card-body">
                        <h3 class="slide-card-title"><?php echo htmlspecialchars($pres['title']); ?></h3>
                        
                        <div class="slide-card-meta">
                            <div class="slide-card-meta-item">
                                <i class="bi bi-layers"></i>
                                <?php echo $slideCount; ?> slides
                            </div>
                            <div class="slide-card-meta-item">
                                <i class="bi bi-clock"></i>
                                <?php echo $duration; ?> phút
                            </div>
                            <div class="slide-card-meta-item">
                                <i class="bi bi-eye"></i>
                                <?php echo $views; ?> lượt
                            </div>
                        </div>
                        
                        <?php if (!empty($pres['tags'])): ?>
                            <div class="slide-card-tags">
                                <?php foreach (array_slice($pres['tags'], 0, 3) as $tag): ?>
                                    <span class="slide-tag"><?php echo htmlspecialchars($tag); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div style="margin-bottom: 1rem;">
                            <small class="text-muted">
                                <i class="bi bi-clock-history"></i> Cập nhật <?php echo $timeAgo; ?>
                            </small>
                        </div>
                        
                        <div class="slide-card-actions">
                            <button class="slide-card-action-btn" onclick="editSlide('<?php echo $pres['id']; ?>')">
                                <i class="bi bi-pencil"></i> Sửa
                            </button>
                            <button class="slide-card-action-btn" onclick="duplicateSlide('<?php echo $pres['id']; ?>')">
                                <i class="bi bi-files"></i> Sao chép
                            </button>
                            <button class="slide-card-action-btn" onclick="deleteSlide('<?php echo $pres['id']; ?>')">
                                <i class="bi bi-trash"></i> Xóa
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Templates Section -->
    <?php if (!empty($templates)): ?>
        <div style="margin-top: 3rem;">
            <h2 class="mb-4">
                <i class="bi bi-palette"></i> Templates Mẫu
            </h2>
            
            <div class="slide-grid">
                <?php foreach ($templates as $template): ?>
                    <div class="slide-card">
                        <div class="slide-card-thumbnail" style="background: <?php 
                            if (isset($template['thumbnail']) && preg_match('/[\x{1F300}-\x{1F9FF}]/u', $template['thumbnail'])) {
                                // If thumbnail is emoji
                                echo 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                            } else {
                                echo $template['color_scheme']['primary'] ?? '#667eea';
                            }
                        ?>">
                            <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: white; font-size: 4rem; font-weight: bold;">
                                <?php 
                                    if (isset($template['thumbnail']) && preg_match('/[\x{1F300}-\x{1F9FF}]/u', $template['thumbnail'])) {
                                        echo $template['thumbnail'];
                                    } else {
                                        echo '<i class="bi bi-palette"></i>';
                                    }
                                ?>
                            </div>
                        </div>
                        
                        <div class="slide-card-body">
                            <h3 class="slide-card-title"><?php echo htmlspecialchars($template['name']); ?></h3>
                            <p class="text-muted small mb-2"><?php echo htmlspecialchars($template['description']); ?></p>
                            
                            <?php if (isset($template['slides']) && is_array($template['slides'])): ?>
                                <div style="background: #f0f8ff; padding: 8px 12px; border-radius: 6px; margin-bottom: 12px; text-align: center;">
                                    <small style="color: #3498db; font-weight: 600;">
                                        <i class="bi bi-file-slides"></i> 
                                        <?php echo count($template['slides']); ?> slides hoàn chỉnh
                                    </small>
                                </div>
                            <?php endif; ?>
                            
                            <button class="slide-btn slide-btn-secondary w-100" onclick="useTemplate('<?php echo $template['id']; ?>')">
                                <i class="bi bi-plus-circle"></i>
                                Sử dụng Template
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Search functionality
document.getElementById('searchInput').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const cards = document.querySelectorAll('.slide-card[data-id]');
    
    cards.forEach(card => {
        const title = card.querySelector('.slide-card-title').textContent.toLowerCase();
        if (title.includes(searchTerm)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
});

// Functions
function presentSlide(id) {
    window.location.href = `slide_presenter.php?id=${id}`;
}

function editSlide(id) {
    window.location.href = `slide_viewer.php?id=${id}`;
}

function duplicateSlide(id) {
    if (confirm('Bạn có muốn sao chép bài giảng này?')) {
        fetch('api/slides/duplicate.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ presentation_id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Lỗi: ' + data.message);
            }
        });
    }
}

function deleteSlide(id) {
    if (confirm('Bạn có chắc muốn xóa bài giảng này? Hành động này không thể hoàn tác.')) {
        fetch('api/slides/delete.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ presentation_id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Lỗi: ' + data.message);
            }
        });
    }
}

function useTemplate(templateId) {
    window.location.href = `slide_builder.php?template=${templateId}`;
}
</script>

<?php include '../includes/teacher_footer.php'; ?>
