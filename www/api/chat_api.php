<?php
// api/chat_api.php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../config/db_connect.php';

if (!$db_connected) {
    echo json_encode(['ok' => false, 'msg' => 'DB 연결 실패']);
    exit;
}

$action = $_GET['action'] ?? '';
$chat_room_id = $_GET['room_id'] ?? ($_POST['room_id'] ?? '');
$user_id = $_SESSION['user_id'] ?? null; // 유저 본인 ID (병원용 앱은 추후 별도 세션 사용)

if (empty($chat_room_id)) {
    echo json_encode(['ok' => false, 'msg' => '채팅방 ID가 없습니다.']);
    exit;
}

if ($action === 'load') {
    // 1. 메시지 불러오기 (Polling 용도)
    $last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
    
    try {
        // last_id 보다 큰 새 메시지만 가져옴
        $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE chat_room_id = ? AND id > ? ORDER BY id ASC");
        $stmt->execute([$chat_room_id, $last_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 날짜/시간 포맷팅
        foreach($messages as &$msg) {
            $date = new DateTime($msg['created_at']);
            $msg['time_str'] = $date->format('a h:i'); // 오전/오후 00:00
            $msg['time_str'] = str_replace(['am', 'pm'], ['오전', '오후'], $msg['time_str']);
        }
        
        echo json_encode(['ok' => true, 'messages' => $messages]);
    } catch(PDOException $e) {
        echo json_encode(['ok' => false, 'msg' => '조회 오류']);
    }

} elseif ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2. 메시지 전송
    $message = trim($_POST['message'] ?? '');
    $sender_type = $_POST['sender_type'] ?? 'user'; // 기본 유저. 추후 어드민/병원은 'hospital'로 전송
    
    if (empty($message)) {
        echo json_encode(['ok' => false, 'msg' => '내용을 입력하세요.']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO chat_messages (chat_room_id, sender_type, sender_id, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$chat_room_id, $sender_type, $user_id, $message]);
        
        $new_id = $pdo->lastInsertId();
        
        // 더미 병원 봇: 유저가 보낸 메시지면 1초 뒤에 자동 응답을 DB에 넣어주는 시뮬레이터 (실제 병원용 앱 도입 전까지 사용)
        if ($sender_type === 'user') {
            $bot_msg = "상담원이 확인 후 답변드리겠습니다. 잠시만 기다려주세요!";
            if (strpos($message, '예약') !== false) {
                $bot_msg = "예약 관련 문의는 원장님 일정 확인 후 확정 안내를 드립니다. 희망 날짜를 남겨주시겠어요?";
            } elseif (strpos($message, '가격') !== false || strpos($message, '얼마') !== false) {
                $bot_msg = "정확한 가격 상담은 방문 후 진단에 따라 달라질 수 있습니다. 이벤트가(VAT별도)를 참고해주세요!";
            }
            
            // 봇 응답을 1초 뒤에 DB에 삽입 (시뮬레이션)
            $stmt = $pdo->prepare("INSERT INTO chat_messages (chat_room_id, sender_type, sender_id, message) VALUES (?, 'hospital', NULL, ?)");
            $stmt->execute([$chat_room_id, $bot_msg]);
        }
        
        echo json_encode(['ok' => true, 'inserted_id' => $new_id]);
    } catch(PDOException $e) {
        echo json_encode(['ok' => false, 'msg' => '저장 오류']);
    }
}
?>
