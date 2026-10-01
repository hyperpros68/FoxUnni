<?php
// www/api/verify_payment.php
require_once '../config/db_connect.php';

header('Content-Type: application/json');

if (!$db_connected) {
    echo json_encode(['success' => false, 'error' => 'DB 연결 실패']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || empty($data['imp_uid']) || empty($data['reservation_id'])) {
    echo json_encode(['success' => false, 'error' => '잘못된 결제 데이터입니다.']);
    exit;
}

$imp_uid = $data['imp_uid'];
$merchant_uid = $data['merchant_uid'] ?? '';
$reservation_id = $data['reservation_id'];
$paid_amount = (float)($data['amount'] ?? 0);

try {
    // 실제 운영 환경에서는 Iamport REST API를 호출하여 imp_uid의 실제 결제 금액을 검증해야 함
    // 여기서는 MVP 단계이므로 클라이언트가 보낸 데이터를 신뢰하고 DB 상태만 업데이트함
    
    // 예약건 조회
    $stmt = $pdo->prepare("SELECT amount FROM reservations WHERE id = ?");
    $stmt->execute([$reservation_id]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$res) {
        echo json_encode(['success' => false, 'error' => '예약 정보를 찾을 수 없습니다.']);
        exit;
    }
    
    // 금액 검증 (클라이언트가 요청한 금액과 DB에 저장된 예약금액이 일치하는지)
    if ((float)$res['amount'] != $paid_amount) {
        // 결제 위변조 의심
        $up = $pdo->prepare("UPDATE reservations SET payment_uid = ?, payment_status = '위변조 의심', status = '결제 에러' WHERE id = ?");
        $up->execute([$imp_uid, $reservation_id]);
        echo json_encode(['success' => false, 'error' => '결제 금액이 일치하지 않습니다.']);
        exit;
    }
    
    // 정상 결제 처리
    $up = $pdo->prepare("UPDATE reservations SET payment_uid = ?, payment_status = '결제완료', status = '예약 확정' WHERE id = ?");
    $up->execute([$imp_uid, $reservation_id]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
