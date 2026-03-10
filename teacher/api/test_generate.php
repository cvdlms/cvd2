<?php
// Test script to check API response
session_name('CVD_TEACHER_SESSION');
session_start();

// Set fake session for testing
$_SESSION['username'] = 'test_teacher';

// Simulate POST request
$_GET['action'] = 'generate';
$_SERVER['REQUEST_METHOD'] = 'POST';

// Mock data
$testData = json_encode([
    'matrix_data' => [
        'topics' => [
            [
                'title' => 'Test Topic',
                'units' => [
                    [
                        'title' => 'Test Unit',
                        'tnkq' => ['nb' => 2, 'th' => 1, 'vd' => 0],
                        'ds' => ['nb' => 0, 'th' => 0, 'vd' => 0],
                        'tl' => ['nb' => 0, 'th' => 0, 'vd' => 0]
                    ]
                ]
            ]
        ]
    ],
    'exam_title' => 'Test Exam',
    'grade' => 'khoi8',
    'subject' => '1',
    'semester' => 'hk1',
    'options' => [
        'create_variants' => 1,
        'randomize_questions' => false,
        'randomize_answers' => false
    ]
]);

// Override php://input
file_put_contents('php://memory', $testData);

// Include the API file
include 'generate_exam.php';
