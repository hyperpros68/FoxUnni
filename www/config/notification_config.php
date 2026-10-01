<?php
// config/notification_config.php
// 향후 실제 서비스 오픈 시 발급받은 API Key로 변경하면 실제 카카오 알림톡/앱 푸시가 전송됩니다.

define('ALIMTALK_API_KEY', 'DUMMY_ALIMTALK_API_KEY');
define('ALIMTALK_API_SECRET', 'DUMMY_ALIMTALK_API_SECRET');
define('ALIMTALK_SENDER_PHONE', '02-1234-5678'); // 여우언니 대표번호

define('FCM_SERVER_KEY', 'DUMMY_FCM_SERVER_KEY');

// 알림톡 템플릿 코드 매핑
$ALIMTALK_TEMPLATES = [
    'booking_wait' => 'TPL_BOOKING_WAIT',       // 결제 후 대기 상태
    'booking_confirm' => 'TPL_BOOKING_CONFIRM', // 병원에서 예약 확정
    'review_request' => 'TPL_REVIEW_REQ'        // 시술 완료 후 리뷰 요청
];
?>
