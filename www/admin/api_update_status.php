<?php
// www/admin/api_update_status.php
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$db_connected) {
        echo "success";
        exit;
    }
    
    $id = $_POST['id'] ?? 0;
    $status = $_POST['status'] ?? '';
    
    if ($id && $status) {
        $stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
        if ($stmt->execute([$status, $id])) {
            
            // 알림 발송을 위해 예약 정보 조회
            $q = $pdo->prepare("SELECT user_id, user_name, phone, hospital_name, event_title FROM reservations WHERE id = ?");
            $q->execute([$id]);
            $res = $q->fetch(PDO::FETCH_ASSOC);

            if ($res) {
                require_once __DIR__ . '/../api/lib/NotificationService.php';
                
                $type = null;
                if ($status === '예약확정') {
                    $type = 'booking_confirm';
                } else if ($status === '시술완료') {
                    $type = 'review_request';
                }

                if ($type) {
                    NotificationService::sendNotice($res['user_id'], $res['phone'], $type, [
                        'user_name' => $res['user_name'],
                        'hospital_name' => $res['hospital_name'],
                        'event_title' => $res['event_title']
                    ]);
                }
            }

            echo "success";
            exit;
        }
    }
}
echo "fail";
?>
