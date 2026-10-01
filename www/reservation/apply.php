<?php
// www/reservation/apply.php
require_once '../config/db_connect.php';

$event_id = $_GET['event_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($db_connected) {
        $user_id = 1; // 샘플 데이터(카리나)
        $event_id = $_POST['event_id'];
        $desired_date = $_POST['desired_date'];
        $desired_part = $_POST['desired_part'];
        $memo = $_POST['memo'];
        
        // hospital_id 알아내기
        $stmt = $pdo->prepare("SELECT hospital_id FROM events WHERE id = ?");
        $stmt->execute([$event_id]);
        $hospital_id = $stmt->fetchColumn();
        
        if ($hospital_id) {
            $insert = $pdo->prepare("INSERT INTO reservations (user_id, event_id, hospital_id, desired_date, desired_part, memo) VALUES (?, ?, ?, ?, ?, ?)");
            $insert->execute([$user_id, $event_id, $hospital_id, $desired_date, $desired_part, $memo]);
        }
    }
    
    echo "<script>alert('상담 신청이 완료되었습니다! 병원에서 곧 연락을 드릴 예정입니다.'); location.href='/index.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>상담 신청 - 여우언니</title>
    <link rel="stylesheet" href="/static/css/shinsa_style.css">
</head>
<body>
    <header class="header">
        <div style="font-size: 18px; font-weight: 700; cursor:pointer;" onclick="history.back()">← 뒤로가기</div>
    </header>

    <main style="padding: 20px 0;">
        <h2 style="padding: 0 20px; margin-bottom: 20px;">상담 신청 정보 입력</h2>
        
        <form method="POST">
            <input type="hidden" name="event_id" value="<?= htmlspecialchars($event_id) ?>">
            
            <div class="form-group">
                <label>신청자 이름</label>
                <input type="text" value="카리나" readonly style="background-color: #f5f5f5;">
            </div>
            
            <div class="form-group">
                <label>연락처</label>
                <input type="text" value="010-1234-5678" readonly style="background-color: #f5f5f5;">
            </div>
            
            <div class="form-group">
                <label>방문 희망일</label>
                <input type="date" name="desired_date" required>
            </div>
            
            <div class="form-group">
                <label>고민 부위 및 희망 시술</label>
                <input type="text" name="desired_part" placeholder="예: 코끝, 턱선 리프팅 등" required>
            </div>
            
            <div class="form-group">
                <label>요청사항 (선택)</label>
                <textarea name="memo" rows="4" placeholder="병원에 남기고 싶은 말씀을 적어주세요."></textarea>
            </div>
            
            <div style="padding: 20px;">
                <button type="submit" style="width: 100%; padding: 15px; background-color: var(--primary-color); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 700; cursor: pointer;">신청 완료하기</button>
            </div>
        </form>
    </main>
</body>
</html>


