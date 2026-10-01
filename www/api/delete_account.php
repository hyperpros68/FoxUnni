<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => '로그인이 필요합니다.']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // 하드 삭제(DELETE) 대신 소프트 삭제(status='deleted') 처리 (일반적인 서비스 방식)
    $stmt = $pdo->prepare("UPDATE users SET status = 'deleted', kakao_id = NULL, nickname = '탈퇴한사용자' WHERE id = ?");
    $stmt->execute([$user_id]);

    // 세션 파기
    session_destroy();
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => '데이터베이스 오류가 발생했습니다.']);
}
?>
