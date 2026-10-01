<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connect.php';

if (!$db_connected) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Read JSON input
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if(!$input || empty($input['event_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? ('앱유저_' . rand(1000, 9999));
$phone = '010-' . rand(1000, 9999) . '-' . rand(1000, 9999);

$event_id = (int)$input['event_id'];
$event_title = $input['event_title'] ?? '';
$hospital_name = $input['hospital_name'] ?? '';
$desired_date = $input['desired_date'] ?? date('Y-m-d');
$desired_times = is_array($input['desired_times']) ? implode(', ', $input['desired_times']) : ($input['desired_times'] ?? '');
$memo = $input['memo'] ?? '여우언니 앱에서 신청됨';
$amount = $input['amount'] ?? 0;
// 변경점: 결제 직후 병원이 전화(해피콜)를 걸어야 하므로 'wait_call' 상태 부여
$status = 'wait_call';

try {
    // 외래키 위반 방지를 위해 events 테이블에 해당 event_id가 없는 경우 임시로 여우언니 단독 특가를 생성합니다.
    $checkEvent = $pdo->prepare("SELECT COUNT(*) FROM events WHERE id = ?");
    $checkEvent->execute([$event_id]);
    $eventExists = $checkEvent->fetchColumn() > 0;

    if (!$eventExists) {
        // 병원 존재 확인 및 생성
        $checkHospital = $pdo->prepare("SELECT id FROM hospitals WHERE name = ?");
        $checkHospital->execute([$hospital_name]);
        $hospital_id = $checkHospital->fetchColumn();

        if (!$hospital_id) {
            $insHosp = $pdo->prepare("INSERT INTO hospitals (name, address, contact, description) VALUES (?, '주소 미상', '연락처 미상', '예약 시 자동 생성된 병원')");
            $insHosp->execute([$hospital_name]);
            $hospital_id = $pdo->lastInsertId();
        }

        // 임시 여우언니 단독 특가 생성
        $insEvent = $pdo->prepare("INSERT INTO events (id, hospital_id, title, category, target_part, original_price, discount_price, image_url, description) VALUES (?, ?, ?, '피부', '체크', 0, 0, '', '예약 시 자동 생성된 여우언니 단독 특가')");
        $insEvent->execute([$event_id, $hospital_id, $event_title]);
    }

    // 예약 데이터 삽입
    $sql = "INSERT INTO reservations (user_id, user_name, phone, event_id, event_title, hospital_name, desired_date, desired_times, memo, status, amount) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $user_id,
        $user_name,
        $phone,
        $event_id,
        $event_title,
        $hospital_name,
        $desired_date,
        $desired_times,
        $memo,
        $status,
        $amount
    ]);
    $newId = $pdo->lastInsertId();
    
    // 알림 엔진 호출 (결제 대기 / 예약 대기)
    require_once __DIR__ . '/lib/NotificationService.php';
    NotificationService::sendNotice($user_id, $phone, 'booking_wait', [
        'user_name' => $user_name,
        'hospital_name' => $hospital_name,
        'event_title' => $event_title
    ]);
    
    // [신규 추가] 병원측 알림톡/SMS 발송 시뮬레이션
    // 실제로는 Aligo, Twilio 등의 SMS 발송 API 연동이 들어갈 자리입니다.
    $hospital_phone = "010-0000-0000"; // 병원 담당자 번호 (추후 병원 정보에서 추출)
    $sms_message = "[여우언니 신규예약]\n{$hospital_name} 원장님, {$user_name} 고객님이 {$event_title} 예약을 신청하셨습니다. 고객님께 해피콜(전화)을 걸어 스케줄을 확정해 주세요! (고객 연락처: {$phone})";
    
    // error_log("[SMS발송성공] To: {$hospital_phone}, Message: {$sms_message}");

    echo json_encode(['success' => true, 'id' => $newId, 'sms_simulated' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>


