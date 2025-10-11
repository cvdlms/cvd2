<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

$examType = $_GET['type'] ?? '';
$gradeLevel = $_GET['grade'] ?? '6';

if (!$examType || !in_array($examType, ['TX1', 'TX2'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid exam type']);
    exit;
}

// Load questions based on grade level
$questionsFile = __DIR__ . '/../../shared/questions/tin' . $gradeLevel . '.js';

if (!file_exists($questionsFile)) {
    echo json_encode(['success' => false, 'message' => 'Questions file not found for grade ' . $gradeLevel]);
    exit;
}

// Read and parse the questions file
$questionsContent = file_get_contents($questionsFile);

// Extract questions from the JavaScript file
preg_match('/questionsByLesson\s*=\s*({.*?});/s', $questionsContent, $matches);

if (!$matches) {
    echo json_encode(['success' => false, 'message' => 'Could not parse questions file']);
    exit;
}

$questionsData = json_decode($matches[1], true);

if (!$questionsData) {
    echo json_encode(['success' => false, 'message' => 'Invalid questions data']);
    exit;
}

// Collect all questions from all lessons
$allQuestions = [];
foreach ($questionsData as $lesson => $questions) {
    foreach ($questions as $question) {
        $question['lesson'] = $lesson;
        $allQuestions[] = $question;
    }
}

// Filter questions based on exam type and difficulty
$filteredQuestions = [];
foreach ($allQuestions as $question) {
    // For TX1 (first semester), include basic and normal level questions
    // For TX2 (second semester), include all levels
    if ($examType === 'TX1') {
        if (in_array($question['level'], ['NB', 'TH'])) {
            $filteredQuestions[] = $question;
        }
    } else {
        $filteredQuestions[] = $question;
    }
}

// Randomly select 40 questions
if (count($filteredQuestions) < 40) {
    echo json_encode(['success' => false, 'message' => 'Not enough questions available']);
    exit;
}

shuffle($filteredQuestions);
$selectedQuestions = array_slice($filteredQuestions, 0, 40);

// Reset array keys
$selectedQuestions = array_values($selectedQuestions);

echo json_encode([
    'success' => true,
    'questions' => $selectedQuestions,
    'total' => count($selectedQuestions)
]);
?>
