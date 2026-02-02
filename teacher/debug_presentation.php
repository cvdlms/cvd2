<?php
/**
 * Debug Presentation Data
 */

session_name('CVD_TEACHER_SESSION');
session_start();

include '../includes/session_check.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../login.php');
    exit;
}

$username = $_SESSION['username'];
$presentationId = $_GET['id'] ?? '';

require_once __DIR__ . '/../includes/PresentationStorage.php';
$storage = new PresentationStorage();

$presentation = null;
if ($presentationId) {
    $presentation = $storage->getById($presentationId);
    
    if (!$presentation || $presentation['teacher_username'] !== $username) {
        die('Không có quyền truy cập bài giảng này');
    }
}

if (!$presentation) {
    die('Không tìm thấy bài giảng');
}

$title = 'Debug Presentation - CVD';
include '../includes/teacher_header.php';
?>

<div class="container mt-4">
    <h2>🔍 Debug Presentation Data</h2>
    
    <div class="card mb-3">
        <div class="card-header">
            <h4>Presentation Info</h4>
        </div>
        <div class="card-body">
            <p><strong>ID:</strong> <?php echo htmlspecialchars($presentation['id']); ?></p>
            <p><strong>Title:</strong> <?php echo htmlspecialchars($presentation['title']); ?></p>
            <p><strong>Source:</strong> <?php echo htmlspecialchars($presentation['source'] ?? 'unknown'); ?></p>
            <p><strong>Total Slides:</strong> <?php echo count($presentation['slides']); ?></p>
        </div>
    </div>
    
    <?php if (isset($presentation['pptx_metadata']['debug_info'])): ?>
    <div class="card mb-3">
        <div class="card-header">
            <h4>Import Debug Info</h4>
        </div>
        <div class="card-body">
            <?php if (isset($presentation['pptx_metadata']['debug_info']['layout'])): ?>
            <div class="alert alert-info mb-3">
                <strong>Layout Info:</strong><br>
                Slide Width (EMU): <?php echo $presentation['pptx_metadata']['debug_info']['layout']['slideWidth_EMU']; ?><br>
                Slide Height (EMU): <?php echo $presentation['pptx_metadata']['debug_info']['layout']['slideHeight_EMU']; ?><br>
                Scale X: <?php echo $presentation['pptx_metadata']['debug_info']['layout']['scaleX']; ?><br>
                Scale Y: <?php echo $presentation['pptx_metadata']['debug_info']['layout']['scaleY']; ?>
            </div>
            <?php endif; ?>
            
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Slide #</th>
                        <th>Shapes Count</th>
                        <th>Elements Parsed</th>
                        <th>Errors</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($presentation['pptx_metadata']['debug_info'] as $slideIdx => $info): ?>
                        <?php if (is_numeric($slideIdx)): ?>
                    <tr>
                        <td><?php echo $slideIdx + 1; ?></td>
                        <td><?php echo $info['shapes_count']; ?></td>
                        <td><?php echo $info['elements_parsed']; ?></td>
                        <td>
                            <?php if (!empty($info['errors'])): ?>
                                <ul style="margin:0; padding-left: 20px;">
                                    <?php foreach ($info['errors'] as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <span class="text-success">No errors</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="card mb-3">
        <div class="card-header">
            <h4>Slides Detail</h4>
        </div>
        <div class="card-body">
            <?php foreach ($presentation['slides'] as $idx => $slide): ?>
            <div class="mb-4 p-3 border rounded">
                <h5>Slide #<?php echo $idx + 1; ?></h5>
                <p><strong>Background:</strong> <?php echo htmlspecialchars($slide['background']); ?></p>
                <p><strong>Elements:</strong> <?php echo count($slide['elements']); ?></p>
                
                <?php 
                // Show raw shape data if available
                if (isset($presentation['pptx_metadata']['debug_info'][$idx]['shapes_raw_data'])): 
                ?>
                <div class="alert alert-warning">
                    <strong>Raw Shape Data:</strong>
                    <pre style="max-height: 200px; overflow: auto; font-size: 11px;"><?php echo json_encode($presentation['pptx_metadata']['debug_info'][$idx]['shapes_raw_data'], JSON_PRETTY_PRINT); ?></pre>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($slide['elements'])): ?>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Type</th>
                            <th>Content Preview</th>
                            <th>Position</th>
                            <th>Size</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($slide['elements'] as $eIdx => $element): ?>
                        <tr>
                            <td><?php echo $eIdx + 1; ?></td>
                            <td><?php echo htmlspecialchars($element['type']); ?></td>
                            <td>
                                <?php 
                                if ($element['type'] === 'text' || $element['type'] === 'heading') {
                                    echo htmlspecialchars(substr($element['content'] ?? '', 0, 50));
                                } elseif ($element['type'] === 'image') {
                                    echo htmlspecialchars($element['content'] ?? 'No path');
                                } elseif ($element['type'] === 'table') {
                                    echo "Table ({$element['rows']}x{$element['cols']})";
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                x: <?php echo $element['position']['x'] ?? 'N/A'; ?>,
                                y: <?php echo $element['position']['y'] ?? 'N/A'; ?>
                            </td>
                            <td>
                                <?php echo $element['size']['width'] ?? 'auto'; ?> x
                                <?php echo $element['size']['height'] ?? 'auto'; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="text-danger">⚠️ No elements found in this slide!</p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="mb-3">
        <a href="slide_viewer.php?id=<?php echo $presentationId; ?>" class="btn btn-primary">View in Viewer</a>
        <a href="slides.php" class="btn btn-secondary">Back to Library</a>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h4>Full JSON Data</h4>
        </div>
        <div class="card-body">
            <pre style="max-height: 400px; overflow: auto; background: #f5f5f5; padding: 15px; border-radius: 5px;"><?php echo json_encode($presentation, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>
        </div>
    </div>
</div>

<?php include '../includes/teacher_footer.php'; ?>
