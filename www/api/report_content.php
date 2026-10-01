<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => '로그인이 필요합니다.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$reporter_id = $_SESSION['user_id'];
$target_type = $input['target_type'] ?? '';
$target_id = (int)($input['target_id'] ?? 0);
$reason = $input['reason'] ?? '';

if (empty($target_type) || empty($target_id) || empty($reason)) {
    echo json_encode(['success' => false, 'error' => '잘못된 신고 요청입니다.']);
    exit;
}

try {
    // 이미 신고했는지 체크
    $check = $pdo->prepare("SELECT id FROM reports WHERE reporter_id = ? AND target_type = ? AND target_id = ?");
    $check->execute([$reporter_id, $target_type, $target_id]);
    if ($check->fetchColumn()) {
        echo json_encode(['success' => false, 'error' => '이미 신고한 콘텐츠입니다.']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO reports (reporter_id, target_type, target_id, reason) VALUES (?, ?, ?, ?)");
    $stmt->execute([$reporter_id, $target_type, $target_id, $reason]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => '데이터베이스 오류: ' . $e->getMessage()]);
}
?>
