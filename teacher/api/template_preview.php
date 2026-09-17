<?php
require_once __DIR__ . '/../../includes/api_auth.php';
requireTeacherSession();

/**
 * Template Preview API - Serves raw template HTML for iframe thumbnails.
 * The template's own rem-scaling (100vw/1366, 100vh/768) makes the slide
 * shrink to fit any iframe with 16:9 (1366x768) aspect ratio.
 */

$templateId = $_GET['id'] ?? '';

if (empty($templateId)) {
    http_response_code(400);
    exit;
}

$metadataFile = __DIR__ . '/../../data/html_templates_metadata.json';
if (file_exists($metadataFile)) {
    $metadata = json_decode(file_get_contents($metadataFile), true);

    if ($metadata && isset($metadata['templates'])) {
        foreach ($metadata['templates'] as $template) {
            if ($template['id'] === $templateId) {
                $templatePath = __DIR__ . '/../../' . $template['file_path'];

                if (file_exists($templatePath)) {
                    header('Content-Type: text/html; charset=utf-8');
                    header('Cache-Control: no-store, no-cache, must-revalidate');
                    readfile($templatePath);
                    exit;
                }
            }
        }
    }
}

http_response_code(404);
echo '<!DOCTYPE html><html><body style="margin:0;background:#2d2d30"></body></html>';