<?php
// www/admin/manage_events.php
require_once '../config/db_connect.php';

// 임시 병원 ID (신사드림성형외과: 1번)
$hospital_id = 1;
$hospital_name = '신사드림성형외과';

// DB에서 해당 병원의 이벤트 목록 가져오기
$events = [];
if ($db_connected) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE hospital_id = ? ORDER BY created_at DESC");
    $stmt->execute([$hospital_id]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>이벤트 관리 - 여우언니 파트너센터</title>
    <style>
        body { font-family: 'Noto Sans KR', sans-serif; background-color: #f4f6f9; margin: 0; display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #fff; border-right: 1px solid #ddd; padding: 20px 0; }
        .sidebar h2 { padding: 0 20px; color: #f5576c; font-size: 20px; margin-bottom: 30px; }
        .nav-item { display: block; padding: 15px 20px; color: #333; text-decoration: none; font-weight: 500; transition: 0.2s; }
        .nav-item:hover, .nav-item.active { background: #fff0f5; color: #f5576c; border-right: 3px solid #f5576c; }
        
        .main-content { flex: 1; padding: 30px; }
        .dashboard-container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header-box { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        h1 { color: #111; margin: 0; font-size: 24px; }
        
        .btn-write { padding: 10px 20px; background: #f5576c; color: white; border: none; border-radius: 8px; font-weight: 700; text-decoration: none; cursor: pointer; transition: 0.2s; }
        .btn-write:hover { background: #e04055; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background-color: #f8f9fa; font-weight: 600; color: #555; }
        
        .event-img { width: 80px; height: 80px; border-radius: 8px; object-fit: cover; }
        .btn-delete { padding: 6px 12px; background: #fff; color: #dc3545; border: 1px solid #dc3545; border-radius: 4px; cursor: pointer; }
        .btn-delete:hover { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>🦊 여우언니 파트너스</h2>
        <a href="crm_dashboard.php" class="nav-item">📅 예약 관리</a>
        <a href="manage_events.php" class="nav-item active">🎁 이벤트(시술) 관리</a>
    </div>

    <div class="main-content">
        <div class="dashboard-container">
            <div class="header-box">
                <h1><?= htmlspecialchars($hospital_name) ?> 등록 시술 관리</h1>
                <a href="event_write.php" class="btn-write">+ 새 이벤트 등록하기</a>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th width="100">이미지</th>
                        <th>카테고리</th>
                        <th>시술명 (이벤트 제목)</th>
                        <th>가격 정보</th>
                        <th>등록일</th>
                        <th width="100">관리</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($events as $ev): ?>
                    <tr>
                        <td>
                            <img src="<?= htmlspecialchars($ev['image_url'] ?: '/static/images/skin.jpg') ?>" class="event-img" alt="이벤트 썸네일">
                        </td>
                        <td><span style="background:#f0f0f0; padding:4px 8px; border-radius:4px; font-size:12px;"><?= htmlspecialchars($ev['category']) ?></span></td>
                        <td>
                            <div style="font-weight:700; font-size:16px; margin-bottom:5px; color:#111;"><?= htmlspecialchars($ev['title']) ?></div>
                            <div style="color:#888; font-size:12px;">적용 부위: <?= htmlspecialchars($ev['target_part']) ?></div>
                        </td>
                        <td>
                            <div style="color:#aaa; text-decoration:line-through; font-size:12px;"><?= number_format($ev['original_price']) ?>원</div>
                            <div style="color:#f5576c; font-weight:700; font-size:18px;"><?= number_format($ev['discount_price']) ?>원</div>
                        </td>
                        <td><?= explode(' ', $ev['created_at'])[0] ?></td>
                        <td>
                            <button class="btn-delete" onclick="deleteEvent(<?= $ev['id'] ?>)">삭제</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($events)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding: 50px 0; color:#888;">
                            현재 등록된 이벤트가 없습니다.<br>
                            우측 상단의 '+ 새 이벤트 등록하기' 버튼을 눌러 시술을 추가해 보세요.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function deleteEvent(id) {
        if(confirm('이 이벤트를 정말 삭제하시겠습니까? (삭제 시 복구 불가)')) {
            fetch('/admin/api_event.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=delete&id=' + id
            })
            .then(res => res.text())
            .then(data => {
                if(data === 'success') {
                    alert('삭제되었습니다.');
                    location.reload();
                } else {
                    alert('삭제 실패: ' + data);
                }
            });
        }
    }
    </script>
</body>
</html>
