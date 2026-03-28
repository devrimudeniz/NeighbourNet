<?php
/**
 * API: Handle Verification Requests (Business & Captain)
 */

require_once '../includes/db.php';
require_once '../includes/optimize_upload.php';
require_once '../includes/security_helper.php';
require_once '../includes/RateLimiter.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

require_csrf();

if (!RateLimiter::check($pdo, 'verify_req', 2, 300)) {
    http_response_code(429);
    echo json_encode(['status' => 'error', 'message' => 'Too many requests. Please try again later.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$request_type = $_POST['request_type'] ?? '';

if (!in_array($request_type, ['business', 'captain', 'real_estate'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request type']);
    exit();
}

// Check for existing pending request
$check = $pdo->prepare("SELECT id FROM verification_requests WHERE user_id = ? AND status = 'pending'");
$check->execute([$user_id]);
if ($check->fetch()) {
    echo json_encode(['status' => 'error', 'message' => 'You already have a pending verification request']);
    exit();
}

try {
    $documentation_file = null;

    // Handle file upload for captain and real_estate verification
    if (in_array($request_type, ['captain', 'real_estate']) && isset($_FILES['documentation'])) {
        $file = $_FILES['documentation'];
        $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only PDF, JPG, PNG allowed.']);
            exit();
        }

        if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
            echo json_encode(['status' => 'error', 'message' => 'File too large. Maximum 5MB.']);
            exit();
        }

        $upload_dir = '../assets/verification_docs/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Conditional Optimization for Docs
        if ($ext === 'pdf') {
            // PDF: Standard Upload
            $filename = 'doc_' . $user_id . '_' . time() . '.' . $ext;
            $filepath = $upload_dir . $filename;
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $documentation_file = 'assets/verification_docs/' . $filename;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'File upload failed']);
                exit();
            }
        } else {
            // Image: Optimize
            $result = gorselOptimizeEt($file, $upload_dir);
            if (isset($result['success'])) {
                $documentation_file = 'assets/verification_docs/' . $result['filename'];
            } else {
                echo json_encode(['status' => 'error', 'message' => $result['error'] ?? 'Image optimization failed']);
                exit();
            }
        }
    }

    // Handle MENU PHOTOS upload
    $menu_photos_paths = [];
    if (in_array($request_type, ['business']) && isset($_FILES['menu_photos'])) {
        $menu_upload_dir = '../assets/verification_docs/menus/';
        if (!is_dir($menu_upload_dir)) {
            mkdir($menu_upload_dir, 0755, true);
        }

        $files = $_FILES['menu_photos'];
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === 0) {
                // Struct single file
                $file_ary = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];
                
                $result = gorselOptimizeEt($file_ary, $menu_upload_dir);
                
                if (isset($result['success'])) {
                    $menu_photos_paths[] = 'assets/verification_docs/menus/' . $result['filename'];
                }
            }
        }
    }

    // Insert verification request
    $stmt = $pdo->prepare("
        INSERT INTO verification_requests 
        (user_id, request_type, business_name, business_category, boat_name, boat_license_number, documentation_file, additional_info, license_number, menu_photos)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $user_id,
        $request_type,
        $_POST['business_name'] ?? null,
        $_POST['business_category'] ?? null,
        $_POST['boat_name'] ?? null,
        $_POST['boat_license_number'] ?? null,
        $documentation_file,
        $_POST['additional_info'] ?? null,
        $documentation_file,
        $_POST['additional_info'] ?? null,
        $_POST['license_number'] ?? null,
        !empty($menu_photos_paths) ? json_encode($menu_photos_paths) : null
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Verification request submitted successfully']);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'An error occurred']);
}
