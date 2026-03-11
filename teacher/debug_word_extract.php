<?php
// Debug script để xem text được extract từ Word file như thế nào

require_once '../vendor/autoload.php';

$wordFile = __DIR__ . '/generated_templates/mau_cau_hoi_word.docx';

if (!file_exists($wordFile)) {
    die("File không tồn tại: $wordFile\n");
}

try {
    $phpWord = \PhpOffice\PhpWord\IOFactory::load($wordFile);
    
    echo "=== EXTRACTING TEXT FROM WORD FILE ===\n\n";
    
    $fullText = '';
    foreach ($phpWord->getSections() as $sectionIndex => $section) {
        echo "--- SECTION $sectionIndex ---\n";
        foreach ($section->getElements() as $elementIndex => $element) {
            $className = get_class($element);
            echo "Element $elementIndex: $className\n";
            
            if (method_exists($element, 'getText')) {
                $text = $element->getText();
                echo "  Text: [$text]\n";
                $fullText .= $text . "\n";
            } elseif (method_exists($element, 'getElements')) {
                echo "  Has child elements:\n";
                foreach ($element->getElements() as $childIndex => $childElement) {
                    $childClassName = get_class($childElement);
                    echo "    Child $childIndex: $childClassName\n";
                    if (method_exists($childElement, 'getText')) {
                        $childText = $childElement->getText();
                        echo "      Text: [$childText]\n";
                        $fullText .= $childText . "\n";
                    }
                }
            }
        }
    }
    
    echo "\n\n=== FULL EXTRACTED TEXT ===\n";
    echo $fullText;
    
    echo "\n\n=== CHECKING FOR PATTERNS ===\n";
    $lines = explode("\n", $fullText);
    foreach ($lines as $i => $line) {
        $line = trim($line);
        if (preg_match('/^Câu\s+\d+:/ui', $line)) {
            echo "Found question at line $i: $line\n";
        }
        if (preg_match('/^Chủ đề:/ui', $line)) {
            echo "Found topic at line $i: $line\n";
        }
        if (preg_match('/^Bài học:/ui', $line)) {
            echo "Found lesson at line $i: $line\n";
        }
        if (preg_match('/^([A-Z])[.)]\s*/i', $line)) {
            echo "Found option at line $i: $line\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
