<?php
// api/confirm_booking.php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../config/db_connect.php';

if (!$db_connected) {
    echo json_encode(['success' => false, 'error' => 'DB 연결 실패']);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("UPDATE reservations SET status = 'confirmed' WHERE id = ?");
        $stmt->execute([$id]);
        
        // (선택) 고객에게 예약 확정 카카오 알림톡/SMS 발송 로직
        // $res = $pdo->query("SELECT user_id, phone FROM reservations WHERE id = $id")->fetch();
        // NotificationService::sendNotice($res['user_id'], $res['phone'], 'booking_confirmed', []);
        
        echo json_encode(['success' => true]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => '업데이트 실패']);
    }
} else {
    echo json_encode(['success' => false, 'error' => '유효하지 않은 예약 ID']);
}
?>
