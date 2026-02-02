<?php
/**
 * PPT/PPTX Uploader - Upload PowerPoint for Online Viewing
 * View via Microsoft Office Online Viewer
 */

session_name('CVD_TEACHER_SESSION');
session_start();

include '../includes/session_check.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../login.php');
    exit;
}

$username = $_SESSION['username'];

// Load teacher's assigned subjects
$teacherSubjectsFile = __DIR__ . '/../admin/teacher_subjects.json';
$subjectsFile = __DIR__ . '/../admin/subjects.json';

$assignedSubjectIds = [];
$allSubjects = [];

if (file_exists($teacherSubjectsFile)) {
    $teacherSubjectsData = json_decode(file_get_contents($teacherSubjectsFile), true);
    $assignedSubjectIds = $teacherSubjectsData[$username] ?? [];
}

if (file_exists($subjectsFile)) {
    $allSubjects = json_decode(file_get_contents($subjectsFile), true);
}

// Filter subjects to only assigned ones
$teacherSubjects = array_filter($allSubjects, function($subject) use ($assignedSubjectIds) {
    return in_array($subject['id'], $assignedSubjectIds);
});

// Ensure upload directory exists
$uploadDir = __DIR__ . '/../uploads/ppt_files';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Load PPT metadata
$metadataFile = __DIR__ . '/../data/ppt_metadata.json';
$pptFiles = file_exists($metadataFile) ? json_decode(file_get_contents($metadataFile), true) : [];

$error = '';
$success = '';

// Handle PPT/PPTX Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['ppt_file'])) {
    try {
        $file = $_FILES['pptx_file'];
        
        // Validation
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload error: ' . $file['error']);
        }
        
        // Check file size (50MB max)
        if ($file['size'] > 50 * 1024 * 1024) {
            throw new Exception('File quá lớn. Giới hạn 50MB.');
        }
        
        // Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedTypes = [
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.ms-powerpoint'
        ];
        
        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('File không hợp lệ. Chỉ chấp nhận file .pptx');
        }
        
        // Generate unique presentation ID
        $presentationId = 'pres_' . uniqid();
        
        // Save original PPTX
        $pptxDir = __DIR__ . '/../uploads/pptx/';
        $pptxFilename = $presentationId . '.pptx';
        $pptxPath = $pptxDir . $pptxFilename;
        
        if (!move_uploaded_file($file['tmp_name'], $pptxPath)) {
            throw new Exception('Không thể lưu file PPTX');
        }
        
        // Parse PPTX with PHPPresentation
        $phpPresentation = IOFactory::load($pptxPath);
        
        // Extract metadata
        $properties = $phpPresentation->getDocumentProperties();
        $title = $_POST['title'] ?? $properties->getTitle() ?? basename($file['name'], '.pptx');
        $slideCount = $phpPresentation->getSlideCount();
        
        // Get slide dimensions from presentation
        $layout = $phpPresentation->getLayout();
        $slideWidth = $layout->getCX(); // in EMUs
        $slideHeight = $layout->getCY(); // in EMUs
        
        // Target canvas size
        $targetWidth = 1920;
        $targetHeight = 1080;
        
        // PowerPoint slide dimensions in pixels (standard 10" x 7.5" at 96 DPI)
        // Most PPTX files use 960x720 as internal coordinate system
        $pptxWidth = 960;  // Standard PowerPoint width
        $pptxHeight = 720; // Standard PowerPoint height
        
        // Calculate scale factors (PowerPoint pixels to our canvas)
        $scaleX = $targetWidth / $pptxWidth;   // 1920 / 960 = 2
        $scaleY = $targetHeight / $pptxHeight; // 1080 / 720 = 1.5
        
        // Convert slides to our JSON format
        $slides = [];
        $imageCounter = 1;
        $debugInfo = [
            'layout' => [
                'slideWidth_EMU' => $slideWidth,
                'slideHeight_EMU' => $slideHeight,
                'pptxWidth' => $pptxWidth,
                'pptxHeight' => $pptxHeight,
                'scaleX' => $scaleX,
                'scaleY' => $scaleY
            ]
        ]; // Debug information
        
        foreach ($phpPresentation->getAllSlides() as $slideIndex => $slide) {
            $slideData = [
                'id' => 'slide_' . uniqid(),
                'type' => 'content',
                'slide_number' => $slideIndex + 1,
                'pptx_source' => true,
                'background' => '#ffffff',
                'elements' => []
            ];
            
            $debugInfo[$slideIndex] = [
                'shapes_count' => 0,
                'elements_parsed' => 0,
                'errors' => [],
                'shapes_raw_data' => [] // Add raw shape data
            ];
            
            // Get background color (safely handle different background types)
            try {
                $background = $slide->getBackground();
                if ($background) {
                    // Check if it's a Color type background
                    if ($background instanceof \PhpOffice\PhpPresentation\Slide\Background\Color) {
                        $color = $background->getColor();
                        if ($color) {
                            $slideData['background'] = '#' . $color->getRGB();
                        }
                    }
                    // Image backgrounds are handled differently (skip for now)
                }
            } catch (Exception $e) {
                // Keep default white background
                $debugInfo[$slideIndex]['errors'][] = 'Background error: ' . $e->getMessage();
            }
            
            // Extract shapes/elements
            $shapeCollection = $slide->getShapeCollection();
            $debugInfo[$slideIndex]['shapes_count'] = count($shapeCollection);
            
            foreach ($shapeCollection as $shapeIdx => $shape) {
                $element = null;
                
                // Capture raw shape data for debugging
                $shapeDebug = [
                    'type' => get_class($shape),
                    'offsetX' => method_exists($shape, 'getOffsetX') ? $shape->getOffsetX() : 'N/A',
                    'offsetY' => method_exists($shape, 'getOffsetY') ? $shape->getOffsetY() : 'N/A',
                    'width' => method_exists($shape, 'getWidth') ? $shape->getWidth() : 'N/A',
                    'height' => method_exists($shape, 'getHeight') ? $shape->getHeight() : 'N/A',
                ];
                $debugInfo[$slideIndex]['shapes_raw_data'][] = $shapeDebug;
                
                try {
                
                // Text shapes
                if ($shape instanceof \PhpOffice\PhpPresentation\Shape\RichText) {
                    $text = '';
                    foreach ($shape->getParagraphs() as $paragraph) {
                        foreach ($paragraph->getRichTextElements() as $richText) {
                            $text .= $richText->getText();
                        }
                        $text .= "\n";
                    }
                    
                    // Get position and size - handle placeholders
                    $offsetX = $shape->getOffsetX();
                    $offsetY = $shape->getOffsetY();
                    $width = $shape->getWidth();
                    $height = $shape->getHeight();
                    
                    // If shape has no position/size (often placeholders or shapes from master slide)
                    // Assign default positions to make content visible
                    if ($width == 0 || $height == 0) {
                        // Skip empty shapes unless they have text content
                        if (empty(trim($text))) {
                            continue; // Skip this shape completely
                        }
                        
                        // Has text but no dimensions - use default content area
                        $offsetX = ($offsetX == 0) ? 60 : $offsetX;
                        $offsetY = ($offsetY == 0) ? 180 : $offsetY;
                        $width = 840;
                        $height = 480;
                    } elseif ($offsetX == 0 && $offsetY == 0) {
                        // Has dimensions but no position - might be background shape
                        // If it's full slide size, it's probably a background
                        if ($width == 960 && $height == 720) {
                            // This is a background shape - skip it
                            continue;
                        }
                    }
                    
                    // Safely get font and paragraph properties
                    $font = null;
                    $alignment = 'left';
                    $paragraphs = $shape->getParagraphs();
                    if (!empty($paragraphs)) {
                        $firstParagraph = $paragraphs[0];
                        $richTextElements = $firstParagraph->getRichTextElements();
                        if (!empty($richTextElements)) {
                            $font = $richTextElements[0]->getFont();
                        }
                        
                        // Get text alignment
                        $align = $firstParagraph->getAlignment();
                        if ($align) {
                            $alignValue = $align->getHorizontal();
                            if ($alignValue === \PhpOffice\PhpPresentation\Style\Alignment::HORIZONTAL_CENTER) {
                                $alignment = 'center';
                            } elseif ($alignValue === \PhpOffice\PhpPresentation\Style\Alignment::HORIZONTAL_RIGHT) {
                                $alignment = 'right';
                            } elseif ($alignValue === \PhpOffice\PhpPresentation\Style\Alignment::HORIZONTAL_JUSTIFY) {
                                $alignment = 'justify';
                            }
                        }
                    }
                    
                    // Build comprehensive style
                    $textStyle = [
                        'fontSize' => $font ? $font->getSize() . 'px' : '16px',
                        'color' => $font && $font->getColor() ? '#' . $font->getColor()->getRGB() : '#000000',
                        'fontWeight' => $font && $font->isBold() ? 'bold' : 'normal',
                        'textAlign' => $alignment
                    ];
                    
                    // Add font family if available
                    if ($font && $font->getName()) {
                        $textStyle['fontFamily'] = $font->getName();
                    }
                    
                    // Add italic
                    if ($font && $font->isItalic()) {
                        $textStyle['fontStyle'] = 'italic';
                    }
                    
                    // Add underline (check if method exists)
                    if ($font && method_exists($font, 'getUnderline')) {
                        $underline = $font->getUnderline();
                        if ($underline && $underline !== \PhpOffice\PhpPresentation\Style\Font::UNDERLINE_NONE) {
                            $textStyle['textDecoration'] = 'underline';
                        }
                    }
                    
                    // Add strikethrough (check if method exists)
                    if ($font && method_exists($font, 'isStrikethrough') && $font->isStrikethrough()) {
                        $textStyle['textDecoration'] = isset($textStyle['textDecoration']) 
                            ? $textStyle['textDecoration'] . ' line-through' 
                            : 'line-through';
                    }
                    
                    $element = [
                        'type' => 'text',
                        'content' => trim($text),
                        'position' => [
                            'x' => round($offsetX * $scaleX),
                            'y' => round($offsetY * $scaleY)
                        ],
                        'size' => [
                            'width' => round($width * $scaleX) . 'px',
                            'height' => round($height * $scaleY) . 'px'
                        ],
                        'style' => $textStyle
                    ];
                }
                
                // Image shapes
                elseif ($shape instanceof \PhpOffice\PhpPresentation\Shape\Drawing\File) {
                    // Extract and save image
                    $imagePath = $shape->getPath();
                    $imageExt = pathinfo($imagePath, PATHINFO_EXTENSION);
                    $imageFilename = $presentationId . '_slide' . ($slideIndex + 1) . '_img' . $imageCounter . '.' . $imageExt;
                    $imageDest = __DIR__ . '/../uploads/slides/' . $imageFilename;
                    
                    if (copy($imagePath, $imageDest)) {
                        $element = [
                            'type' => 'image',
                            'content' => 'uploads/slides/' . $imageFilename,
                            'pptx_original' => $imagePath,
                            'missing' => false,
                            'position' => [
                                'x' => round($shape->getOffsetX() * $scaleX),
                                'y' => round($shape->getOffsetY() * $scaleY)
                            ],
                            'size' => [
                                'width' => round($shape->getWidth() * $scaleX) . 'px',
                                'height' => round($shape->getHeight() * $scaleY) . 'px'
                            ]
                        ];
                        $imageCounter++;
                    }
                }
                
                // Shape objects (rectangles, circles, etc.)
                elseif ($shape instanceof \PhpOffice\PhpPresentation\Shape\AbstractDrawing) {
                    try {
                        $shapeType = 'rectangle'; // default
                        
                        // Try to determine shape type
                        if (method_exists($shape, 'getShapeType')) {
                            $shapeType = $shape->getShapeType();
                        }
                        
                        $element = [
                            'type' => 'shape',
                            'shapeType' => $shapeType,
                            'position' => [
                                'x' => round($shape->getOffsetX() * $scaleX),
                                'y' => round($shape->getOffsetY() * $scaleY)
                            ],
                            'size' => [
                                'width' => round($shape->getWidth() * $scaleX) . 'px',
                                'height' => round($shape->getHeight() * $scaleY) . 'px'
                            ],
                            'style' => [
                                'backgroundColor' => 'transparent',
                                'borderColor' => '#000000',
                                'borderWidth' => '2px'
                            ]
                        ];
                    } catch (Exception $e) {
                        // Skip if can't parse shape
                    }
                }
                
                // Table support (basic)
                elseif ($shape instanceof \PhpOffice\PhpPresentation\Shape\Table) {
                    try {
                        $tableData = [];
                        $rowCount = $shape->getRowCount();
                        $colCount = $shape->getColumnCount();
                        
                        // Extract table data
                        for ($row = 0; $row < $rowCount; $row++) {
                            $rowData = [];
                            for ($col = 0; $col < $colCount; $col++) {
                                $cell = $shape->getCell($col, $row);
                                $cellText = '';
                                foreach ($cell->getParagraphs() as $para) {
                                    foreach ($para->getRichTextElements() as $richText) {
                                        $cellText .= $richText->getText();
                                    }
                                }
                                $rowData[] = $cellText;
                            }
                            $tableData[] = $rowData;
                        }
                        
                        // Store as special text element with table data
                        $element = [
                            'type' => 'table',
                            'tableData' => $tableData,
                            'rows' => $rowCount,
                            'cols' => $colCount,
                            'position' => [
                                'x' => round($shape->getOffsetX() * $scaleX),
                                'y' => round($shape->getOffsetY() * $scaleY)
                            ],
                            'size' => [
                                'width' => round($shape->getWidth() * $scaleX) . 'px',
                                'height' => round($shape->getHeight() * $scaleY) . 'px'
                            ]
                        ];
                    } catch (Exception $e) {
                        // Skip table if can't parse
                    }
                }
                
                // Add element if parsed successfully
                if ($element) {
                    $slideData['elements'][] = $element;
                    $debugInfo[$slideIndex]['elements_parsed']++;
                }
                
                } catch (Exception $e) {
                    $debugInfo[$slideIndex]['errors'][] = "Shape $shapeIdx error: " . $e->getMessage();
                }
            }
            
            $slides[] = $slideData;
        }
        
        // Create presentation data
        $presentation = [
            'id' => $presentationId,
            'title' => $title,
            'description' => $_POST['description'] ?? '',
            'teacher_username' => $username,
            'subject_id' => $_POST['subject_id'] ?? '',
            'grade' => $_POST['grade'] ?? '',
            'class_names' => [],
            'source' => 'pptx',
            'pptx_metadata' => [
                'original_filename' => $file['name'],
                'uploaded_at' => date('Y-m-d H:i:s'),
                'slide_count_original' => $slideCount,
                'pptx_path' => 'uploads/pptx/' . $pptxFilename,
                'file_size' => $file['size'],
                'debug_info' => $debugInfo // Add debug info
            ],
            'created_at' => date('Y-m-d H:i:s'),
            'settings' => [
                'theme' => 'imported',
                'transition' => 'slide',
                'auto_play' => false
            ],
            'slides' => $slides
        ];
        
        // Save presentation
        $storage->save($presentation);
        
        // Count total elements parsed
        $totalElements = 0;
        foreach ($slides as $slide) {
            $totalElements += count($slide['elements']);
        }
        
        $success = "✅ Import thành công! Đã tạo {$slideCount} slides với {$totalElements} elements từ file PowerPoint.";
        
        // Redirect to viewer
        header("Location: slide_viewer.php?id={$presentationId}");
        exit;
        
    } catch (Exception $e) {
        $error = '❌ Lỗi: ' . $e->getMessage();
    }
}

$title = 'Import PowerPoint - CVD';
include '../includes/teacher_header.php';
?>

<link rel="stylesheet" href="../styles/slide-system.css">

<div class="slide-library-container">
    <div class="slide-library-header">
        <h1><i class="bi bi-cloud-upload"></i> Import PowerPoint</h1>
        <p>Upload file .pptx để tạo bài giảng trực tuyến</p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="card" style="max-width: 800px; margin: 2rem auto; padding: 2rem;">
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-4">
                <label class="form-label" style="font-weight: 600; font-size: 1.1rem;">
                    <i class="bi bi-file-earmark-ppt"></i> Chọn File PowerPoint (.pptx)
                </label>
                <input type="file" 
                       name="pptx_file" 
                       class="form-control form-control-lg" 
                       accept=".pptx" 
                       required>
                <small class="text-muted">Giới hạn: 50MB. Chỉ hỗ trợ file .pptx (PowerPoint 2007 trở lên)</small>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Tên Bài Giảng</label>
                <input type="text" 
                       name="title" 
                       class="form-control" 
                       placeholder="Tự động lấy từ file nếu không nhập">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Mô Tả</label>
                <textarea name="description" 
                          class="form-control" 
                          rows="3" 
                          placeholder="Mô tả ngắn về bài giảng..."></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Môn Học</label>
                    <select name="subject_id" class="form-select">
                        <option value="">-- Chọn môn học --</option>
                        <?php foreach ($teacherSubjects as $subject): ?>
                            <option value="<?php echo $subject['id']; ?>">
                                <?php echo htmlspecialchars($subject['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Khối Lớp</label>
                    <select name="grade" class="form-select">
                        <option value="">-- Chọn khối --</option>
                        <option value="khoi6">Khối 6</option>
                        <option value="khoi7">Khối 7</option>
                        <option value="khoi8">Khối 8</option>
                        <option value="khoi9">Khối 9</option>
                    </select>
                </div>
            </div>
            
            <div class="alert alert-info">
                <strong><i class="bi bi-info-circle"></i> Lưu ý:</strong>
                <ul class="mb-0 mt-2">
                    <li>Hệ thống sẽ tự động trích xuất text, hình ảnh từ PowerPoint</li>
                    <li>Hình ảnh nhúng (embedded) trong slide sẽ được tách ra tự động</li>
                    <li>Bạn có thể chỉnh sửa, sửa lỗi sau khi import trong chế độ View/Fix</li>
                    <li>File PowerPoint gốc sẽ được lưu trữ để backup</li>
                </ul>
            </div>
            
            <div class="d-flex gap-2 justify-content-end mt-4">
                <a href="slides.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Hủy
                </a>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-upload"></i> Upload & Import
                </button>
            </div>
        </form>
    </div>
    
    <div class="card" style="max-width: 800px; margin: 2rem auto; padding: 2rem; background: #f8f9fa;">
        <h4><i class="bi bi-question-circle"></i> Hướng Dẫn</h4>
        <ol>
            <li><strong>Chuẩn bị file PowerPoint:</strong> Đảm bảo file .pptx, không quá 50MB</li>
            <li><strong>Upload:</strong> Chọn file và điền thông tin bài giảng</li>
            <li><strong>Xem trước:</strong> Hệ thống sẽ chuyển đến màn hình xem/sửa</li>
            <li><strong>Sửa lỗi:</strong> Kiểm tra và fix các hình ảnh/video bị lỗi (nếu có)</li>
            <li><strong>Trình chiếu:</strong> Sẵn sàng để dạy!</li>
        </ol>
    </div>
</div>

<?php include '../includes/teacher_footer.php'; ?>
