<?php
require_once '../config/db_connect.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => '잘못된 요청 방식입니다.']);
    exit;
}

$user_name = $_SESSION['user_name'] ?? '익명' . rand(100, 999);
$target_parts = $_POST['target_parts'] ?? '';
$concerns = $_POST['concerns'] ?? '';
$photo_url = '';

if (empty($target_parts) || empty($concerns)) {
    echo json_encode(['success' => false, 'error' => '희망 부위와 고민 내용을 모두 입력해주세요.']);
    exit;
}

// 파일 업로드 처리 (Mock or 실제)
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $filename = time() . '_' . basename($_FILES['photo']['name']);
    $targetPath = $uploadDir . $filename;
    
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
        $photo_url = '/uploads/' . $filename;
    }
} else if (isset($_POST['photo_base64']) && !empty($_POST['photo_base64'])) {
    // 혹시 Base64 형태의 이미지 데이터가 올 경우
    $base64 = $_POST['photo_base64'];
    if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
        $data = substr($base64, strpos($base64, ',') + 1);
        $type = strtolower($type[1]); // jpg, png, gif
        
        $base64 = str_replace(' ', '+', $data);
        $data = base64_decode($base64);
        
        $filename = time() . '_' . rand(1000,9999) . '.' . $type;
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        file_put_contents($uploadDir . $filename, $data);
        $photo_url = '/uploads/' . $filename;
    }
}

try {
    // virtual_consultations 테이블이 없을 경우를 대비해 스키마 추가 구문 실행 (안전장치)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS virtual_consultations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_name VARCHAR(100) NOT NULL,
            target_parts VARCHAR(255) NOT NULL,
            concerns TEXT NOT NULL,
            photo_url VARCHAR(255) NULL,
            status VARCHAR(50) DEFAULT '견적 대기 중',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $stmt = $pdo->prepare("INSERT INTO virtual_consultations (user_name, target_parts, concerns, photo_url) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$user_name, $target_parts, $concerns, $photo_url])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => '데이터베이스 저장 실패']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'DB 에러: ' . $e->getMessage()]);
}
?>
