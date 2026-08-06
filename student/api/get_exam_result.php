<?php
header('Content-Type: application/json; charset=utf-8');

try {
    session_name('CVD_STUDENT_SESSION');
    session_start();
    if (!isset($_SESSION['student_code'])) {
        echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
        exit;
    }

    $examId = $_GET['exam_id'] ?? '';
    $studentCode = $_SESSION['student_code'];

    if (!$examId) {
        echo json_encode(['success' => false, 'message' => 'Thiếu Exam ID']);
        exit;
    }

    // Load scores data helper
    $scoresFile = __DIR__ . '/../../shared/api/scores.php';
    if (file_exists($scoresFile)) {
        require_once $scoresFile;
    }

    $examResult = null;

    // 1. Search in student's individual score file (shared/scores/{studentCode}.json)
    $studentFile = __DIR__ . '/../../shared/scores/' . $studentCode . '.json';
    if (file_exists($studentFile)) {
        $studentScores = json_decode(file_get_contents($studentFile), true) ?: [];
        foreach ($studentScores as $score) {
            $idMatch = (isset($score['id']) && $score['id'] === $examId) ||
                       (isset($score['result_id']) && $score['result_id'] === $examId);
            if ($idMatch && (($score['student_code'] ?? $score['student_id'] ?? '') === $studentCode)) {
                $examResult = $score;
                break;
            }
        }
    }

    // 2. Search in practice results (data/practice_results/practice_results.json)
    if (!$examResult) {
        $practiceFile = __DIR__ . '/../../data/practice_results/practice_results.json';
        if (file_exists($practiceFile)) {
            $practiceResults = json_decode(file_get_contents($practiceFile), true) ?: [];
            foreach ($practiceResults as $result) {
                $idMatch = (isset($result['id']) && $result['id'] === $examId) ||
                           (isset($result['result_id']) && $result['result_id'] === $examId);
                if ($idMatch && (($result['student_code'] ?? $result['student_id'] ?? '') === $studentCode)) {
                    $examResult = $result;
                    break;
                }
            }
        }
    }

    // 3. Search in consolidated student_score.json or all scores
    if (!$examResult && function_exists('getAllScores')) {
        $allScores = getAllScores();
        foreach ($allScores as $score) {
            $idMatch = (isset($score['id']) && $score['id'] === $examId) ||
                       (isset($score['result_id']) && $score['result_id'] === $examId);
            if ($idMatch && (($score['student_code'] ?? $score['student_id'] ?? '') === $studentCode)) {
                $examResult = $score;
                break;
            }
        }
    }

    if ($examResult) {
        $examResult['test_name'] = $examResult['test_name'] ?? 'Bài kiểm tra trắc nghiệm';
        echo json_encode([
            'success' => true,
            'result' => $examResult
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy kết quả bài thi']);
    }

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
