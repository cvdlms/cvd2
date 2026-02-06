<?php
session_name('CVD_TEACHER_SESSION');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check Premium status
require_once __DIR__ . '/../../includes/premium_helper.php';
$username = $_SESSION['username'];
if (!isPremiumUser($username)) {
    echo json_encode(['success' => false, 'message' => 'Chức năng này chỉ dành cho giáo viên Premium']);
    exit;
}

$username = $_SESSION['username'];
$assignmentsFile = __DIR__ . '/../../data/assignments.json';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// Load assignments
$assignments = file_exists($assignmentsFile) ? json_decode(file_get_contents($assignmentsFile), true) : [];
if (!is_array($assignments)) $assignments = [];

function normalizeClassNames($input, $current = null) {
    $raw = $input['class_names'] ?? $input['class_name'] ?? ($current['class_names'] ?? $current['class_name'] ?? []);

    if (is_string($raw)) {
        $raw = [$raw];
    }

    $normalized = [];
    if (is_array($raw)) {
        foreach ($raw as $value) {
            $value = trim((string)$value);
            if ($value !== '') {
                $normalized[] = $value;
            }
        }
    }

    return array_values(array_unique($normalized));
}

switch ($action) {
    case 'create':
        $classNames = normalizeClassNames($input);
        $newAssignment = [
            'id' => uniqid('assign_'),
            'teacher_username' => $username,
            'title' => $input['title'] ?? '',
            'subject_id' => $input['subject_id'] ?? '',
            'class_names' => $classNames,
            'class_name' => $classNames[0] ?? ($input['class_name'] ?? ''),
            'description' => $input['description'] ?? '',
            'due_date' => $input['due_date'] ?? '',
            'max_score' => intval($input['max_score'] ?? 10),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $assignments[] = $newAssignment;
        file_put_contents($assignmentsFile, json_encode($assignments, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        echo json_encode(['success' => true, 'message' => 'Assignment created', 'assignment' => $newAssignment]);
        break;
        
    case 'update':
        $id = $input['id'] ?? '';
        $found = false;
        
        foreach ($assignments as &$assignment) {
            if ($assignment['id'] === $id && $assignment['teacher_username'] === $username) {
                $assignment['title'] = $input['title'] ?? $assignment['title'];
                $assignment['subject_id'] = $input['subject_id'] ?? $assignment['subject_id'];
                if (isset($input['class_names']) || isset($input['class_name'])) {
                    $classNames = normalizeClassNames($input, $assignment);
                    $assignment['class_names'] = $classNames;
                    $assignment['class_name'] = $classNames[0] ?? ($input['class_name'] ?? ($assignment['class_name'] ?? ''));
                }
                $assignment['description'] = $input['description'] ?? $assignment['description'];
                $assignment['due_date'] = $input['due_date'] ?? $assignment['due_date'];
                $assignment['max_score'] = intval($input['max_score'] ?? $assignment['max_score']);
                $assignment['updated_at'] = date('Y-m-d H:i:s');
                $found = true;
                break;
            }
        }
        
        if ($found) {
            file_put_contents($assignmentsFile, json_encode($assignments, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo json_encode(['success' => true, 'message' => 'Assignment updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Assignment not found']);
        }
        break;
        
    case 'delete':
        $id = $input['id'] ?? '';
        $initialCount = count($assignments);
        
        $assignments = array_filter($assignments, function($assignment) use ($id, $username) {
            return !($assignment['id'] === $id && $assignment['teacher_username'] === $username);
        });
        
        if (count($assignments) < $initialCount) {
            file_put_contents($assignmentsFile, json_encode(array_values($assignments), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo json_encode(['success' => true, 'message' => 'Assignment deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Assignment not found']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
