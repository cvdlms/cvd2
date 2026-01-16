<?php
session_name('CVD_TEACHER_SESSION');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pricingFile = __DIR__ . '/../premium_pricing.json';

// Handle GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'get') {
        $type = $_GET['type'] ?? '';
        $id = $_GET['id'] ?? '';
        
        if (!file_exists($pricingFile)) {
            echo json_encode(['success' => false, 'message' => 'Pricing file not found']);
            exit;
        }
        
        $pricing = json_decode(file_get_contents($pricingFile), true);
        
        if (!isset($pricing[$type])) {
            echo json_encode(['success' => false, 'message' => 'Invalid type']);
            exit;
        }
        
        $package = null;
        foreach ($pricing[$type] as $pkg) {
            if ($pkg['id'] === $id) {
                $package = $pkg;
                break;
            }
        }
        
        if ($package) {
            echo json_encode(['success' => true, 'data' => $package]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Package not found']);
        }
        exit;
    }
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if (!file_exists($pricingFile)) {
        echo json_encode(['success' => false, 'message' => 'Pricing file not found']);
        exit;
    }
    
    $pricing = json_decode(file_get_contents($pricingFile), true);
    
    if ($action === 'add') {
        $type = $_POST['type'] ?? '';
        
        if (!isset($pricing[$type])) {
            echo json_encode(['success' => false, 'message' => 'Invalid type']);
            exit;
        }
        
        $newPackage = [
            'id' => $type . '_' . time(),
            'name' => $_POST['name'] ?? '',
            'duration_days' => (int)($_POST['duration_days'] ?? 0),
            'price' => (int)($_POST['price'] ?? 0),
            'currency' => 'VND',
            'features' => json_decode($_POST['features'] ?? '[]', true),
            'is_active' => isset($_POST['is_active']),
        ];
        
        $discount = (int)($_POST['discount'] ?? 0);
        if ($discount > 0) {
            $newPackage['discount'] = $discount;
        }
        
        $pricing[$type][] = $newPackage;
        
        if (file_put_contents($pricingFile, json_encode($pricing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo json_encode(['success' => true, 'message' => 'Package added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save']);
        }
        exit;
    }
    
    if ($action === 'edit') {
        $type = $_POST['type'] ?? '';
        $id = $_POST['id'] ?? '';
        
        if (!isset($pricing[$type])) {
            echo json_encode(['success' => false, 'message' => 'Invalid type']);
            exit;
        }
        
        $found = false;
        foreach ($pricing[$type] as &$pkg) {
            if ($pkg['id'] === $id) {
                $pkg['name'] = $_POST['name'] ?? $pkg['name'];
                $pkg['duration_days'] = (int)($_POST['duration_days'] ?? $pkg['duration_days']);
                $pkg['price'] = (int)($_POST['price'] ?? $pkg['price']);
                $pkg['features'] = json_decode($_POST['features'] ?? '[]', true) ?: $pkg['features'];
                $pkg['is_active'] = isset($_POST['is_active']);
                
                $discount = (int)($_POST['discount'] ?? 0);
                if ($discount > 0) {
                    $pkg['discount'] = $discount;
                } else {
                    unset($pkg['discount']);
                }
                
                $found = true;
                break;
            }
        }
        
        if ($found) {
            if (file_put_contents($pricingFile, json_encode($pricing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                echo json_encode(['success' => true, 'message' => 'Package updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to save']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Package not found']);
        }
        exit;
    }
    
    if ($action === 'toggle') {
        $type = $_POST['type'] ?? '';
        $id = $_POST['id'] ?? '';
        
        if (!isset($pricing[$type])) {
            echo json_encode(['success' => false, 'message' => 'Invalid type']);
            exit;
        }
        
        $found = false;
        foreach ($pricing[$type] as &$pkg) {
            if ($pkg['id'] === $id) {
                $pkg['is_active'] = !($pkg['is_active'] ?? true);
                $found = true;
                break;
            }
        }
        
        if ($found) {
            if (file_put_contents($pricingFile, json_encode($pricing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                echo json_encode(['success' => true, 'message' => 'Package status updated']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to save']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Package not found']);
        }
        exit;
    }
    
    if ($action === 'delete') {
        $type = $_POST['type'] ?? '';
        $id = $_POST['id'] ?? '';
        
        if (!isset($pricing[$type])) {
            echo json_encode(['success' => false, 'message' => 'Invalid type']);
            exit;
        }
        
        $pricing[$type] = array_filter($pricing[$type], function($pkg) use ($id) {
            return $pkg['id'] !== $id;
        });
        
        $pricing[$type] = array_values($pricing[$type]); // Re-index array
        
        if (file_put_contents($pricingFile, json_encode($pricing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo json_encode(['success' => true, 'message' => 'Package deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save']);
        }
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>
