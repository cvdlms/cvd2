<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Get parameters
$grade = $_GET['grade'] ?? 'khoi7';
$subject = $_GET['subject'] ?? 'subject_1';
$topic = $_GET['topic'] ?? '';
$lesson = $_GET['lesson'] ?? '';
$limit = (int)($_GET['limit'] ?? 10);

// Load system config to get current semester
$configFile = __DIR__ . '/../admin/system_config.json';
$config = json_decode(file_get_contents($configFile), true);
$currentSemester = $config['semester']['current'] ?? 'hk1';

// Build file path
$questionsFile = __DIR__ . '/../teacher/questions/' . $grade . '/' . $currentSemester . '/' . $subject . '.json';

if (!file_exists($questionsFile)) {
    echo json_encode(['success' => false, 'message' => 'Questions file not found']);
    exit;
}

// Read questions data
$questionsData = json_decode(file_get_contents($questionsFile), true);
if (!$questionsData) {
    echo json_encode(['success' => false, 'message' => 'Invalid questions data']);
    exit;
}

// Flatten questions from all topics/lessons
$allQuestions = [];
foreach ($questionsData as $topicData) {
    $currentTopic = $topicData['topic'] ?? '';
    $currentLesson = $topicData['lesson'] ?? '';

    if ($topic && $currentTopic !== $topic) continue;
    if ($lesson && $currentLesson !== $lesson) continue;

    if (isset($topicData['questions']) && is_array($topicData['questions'])) {
        foreach ($topicData['questions'] as $question) {
            $question['topic'] = $currentTopic;
            $question['lesson'] = $currentLesson;
            $allQuestions[] = $question;
        }
    }
}

// Shuffle questions
shuffle($allQuestions);

// Limit number of questions
$selectedQuestions = array_slice($allQuestions, 0, $limit);

// Process each question: shuffle options and remove correct answer
$processedQuestions = [];
foreach ($selectedQuestions as $index => $question) {
    $processedQuestion = [
        'id' => $index + 1,
        'question' => $question['question'],
        'topic' => $question['topic'],
        'lesson' => $question['lesson'],
        'type' => $question['type'] ?? 'single',
        'level' => $question['level'] ?? 'TH'
    ];

    // Shuffle options
    $options = $question['options'];
    $correctIndex = $question['correct'];
    $shuffledIndices = range(0, count($options) - 1);
    shuffle($shuffledIndices);

    // Find new correct index/indices after shuffling
    if (is_array($correctIndex)) {
        // Multiple choice: correct is an array
        $newCorrectIndex = [];
        foreach ($correctIndex as $idx) {
            $newCorrectIndex[] = array_search($idx, $shuffledIndices);
        }
    } else {
        // Single choice: correct is an integer
        $newCorrectIndex = array_search($correctIndex, $shuffledIndices);
    }

    // Reorder options
    $shuffledOptions = [];
    foreach ($shuffledIndices as $oldIndex) {
        $shuffledOptions[] = $options[$oldIndex];
    }

    $processedQuestion['options'] = $shuffledOptions;
    $processedQuestion['correct'] = $newCorrectIndex; // Corrected key for frontend

    $processedQuestions[] = $processedQuestion;
}

echo json_encode([
    'success' => true,
    'questions' => $processedQuestions,
    'total' => count($processedQuestions),
    'filters' => [
        'grade' => $grade,
        'subject' => $subject,
        'topic' => $topic,
        'lesson' => $lesson
    ]
]);
?>
