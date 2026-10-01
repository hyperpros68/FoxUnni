<?php
// api/save_review.php — 후기 저장 API
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => '잘못된 요청입니다.']);
    exit;
}

$user_id    = $_SESSION['user_id'] ?? null;
$user_name  = $_SESSION['user_name'] ?? '익명';
// 이메일 형식이면 '익명'으로 처리
if (strpos($user_name, '@') !== false) $user_name = '익명';

$hospital_name = trim($_POST['hospital_name'] ?? '');
$event_id      = (int)($_POST['event_id'] ?? 0) ?: null;
$event_title   = trim($_POST['event_title'] ?? '');
$rating        = (int)($_POST['rating'] ?? 0);
$content       = trim($_POST['content'] ?? '');

// 유효성 검사
if (empty($hospital_name) || empty($content) || $rating < 1 || $rating > 5) {
    echo json_encode(['ok' => false, 'msg' => '필수 항목을 모두 입력해주세요.']);
    exit;
}
if (mb_strlen($content) < 10) {
    echo json_encode(['ok' => false, 'msg' => '후기는 10자 이상 입력해주세요.']);
    exit;
}

// 사진 처리 (다중 업로드)
$photo_urls = [];
$is_photo_review = 0;

if (isset($_FILES['photo']) && is_array($_FILES['photo']['name'])) {
    $upload_dir = __DIR__ . '/../static/uploads/reviews/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $file_count = count($_FILES['photo']['name']);
    for ($i = 0; $i < $file_count; $i++) {
        if ($_FILES['photo']['error'][$i] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['photo']['name'][$i], PATHINFO_EXTENSION);
            $safe_ext = in_array(strtolower($ext), ['jpg','jpeg','png','gif','webp']) ? strtolower($ext) : 'jpg';
            $file_name = 'review_' . time() . '_' . rand(1000, 9999) . '_' . $i . '.' . $safe_ext;
            $dest = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['photo']['tmp_name'][$i], $dest)) {
                $photo_urls[] = '/static/uploads/reviews/' . $file_name;
            }
        }
    }
}

$photo_url_json = null;
if (count($photo_urls) > 0) {
    $photo_url_json = json_encode($photo_urls, JSON_UNESCAPED_UNICODE);
    $is_photo_review = 1;
}

// DB 저장
if (!$db_connected) {
    echo json_encode(['ok' => false, 'msg' => 'DB 연결 실패']);
    exit;
}

// 영수증 인증 플래그
$is_receipt_verified = (int)($_POST['is_receipt_verified'] ?? 0);

try {
    $stmt = $pdo->prepare("
        INSERT INTO reviews (user_id, user_name, hospital_name, event_id, event_title, rating, content, photo_url, is_photo_review, is_receipt_verified, points_given)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    $stmt->execute([$user_id, $user_name, $hospital_name, $event_id, $event_title, $rating, $content, $photo_url_json, $is_photo_review, $is_receipt_verified]);

    // 포인트 지급: 사진 리뷰면 1500P, 일반 1000P
    $points_earned = $is_photo_review ? 1500 : 1000;

    if ($user_id) {
        $updateStmt = $pdo->prepare("UPDATE users SET points = points + ? WHERE id = ?");
        $updateStmt->execute([$points_earned, $user_id]);
        
        $logStmt = $pdo->prepare("INSERT INTO point_logs (user_id, amount, reason) VALUES (?, ?, ?)");
        $logStmt->execute([$user_id, $points_earned, "리뷰 작성 보상"]);
    }

    echo json_encode([
        'ok'     => true,
        'msg'    => '후기가 등록되었습니다!',
        'points' => $points_earned,
        'is_photo' => $is_photo_review
    ]);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => '저장 중 오류가 발생했습니다: ' . $e->getMessage()]);
}
?>
