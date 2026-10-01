<?php
// www/admin/crm_dashboard.php
require_once '../config/db_connect.php';

// 병원 1번(신사드림성형외과)으로 로그인했다고 가정
$hospital_id = 1;

if ($db_connected) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM reservations ORDER BY created_at DESC");
        $stmt->execute();
        $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $reservations = [];
    }
} else {
    $reservations = [];
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>예약 관리 - 여우언니 파트너센터</title>
    <style>
        body { font-family: 'Noto Sans KR', sans-serif; background-color: #f4f6f9; margin: 0; display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #fff; border-right: 1px solid #ddd; padding: 20px 0; }
        .sidebar h2 { padding: 0 20px; color: #f5576c; font-size: 20px; margin-bottom: 30px; }
        .nav-item { display: block; padding: 15px 20px; color: #333; text-decoration: none; font-weight: 500; transition: 0.2s; }
        .nav-item:hover, .nav-item.active { background: #fff0f5; color: #f5576c; border-right: 3px solid #f5576c; }
        
        .main-content { flex: 1; padding: 30px; }
        .dashboard-container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h1 { color: #111; margin-top: 0; font-size: 24px; margin-bottom: 25px; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background-color: #f8f9fa; font-weight: 600; color: #555; }
        select { padding: 8px 12px; border-radius: 6px; border: 1px solid #ccc; outline: none; }
        .status-btn { padding: 8px 15px; background: #333; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: 0.2s; }
        .status-btn:hover { background: #f5576c; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>🦊 여우언니 파트너스</h2>
        <a href="crm_dashboard.php" class="nav-item active">📅 예약 관리</a>
        <a href="manage_events.php" class="nav-item">🎁 이벤트(시술) 관리</a>
    </div>

    <div class="main-content">
        <div class="dashboard-container">
            <h1>신사드림성형외과 예약 현황</h1>
        
        <table>
            <thead>
                <tr>
                    <th>신청일시</th>
                    <th>고객명</th>
                    <th>연락처</th>
                    <th>신청 여우언니 단독 특가</th>
                    <th>희망일 / 부위</th>
                    <th>요청사항</th>
                    <th>상태</th>
                    <th>관리</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($reservations as $r): ?>
                <tr>
                    <td><?= $r['id'] ?></td>
                    <td>
                        <?= htmlspecialchars($r['created_at']) ?>
                        <?php if(($r['payment_status'] ?? '') === '결제완료'): ?>
                            <br><span style="background-color: #f6ffed; color: #52c41a; font-size:11px; padding:2px 6px; border-radius:4px; font-weight:bold; border: 1px solid #b7eb8f;">💳 결제완료</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($r['user_name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($r['phone'] ?? '') ?></td>
                    <td><?= htmlspecialchars($r['event_title'] ?? '') ?></td>
                    <td>
                        <div style="font-weight:bold; color:#007bff;"><?= htmlspecialchars($r['desired_date'] ?? '') ?></div>
                        <?php 
                            $times = [];
                            if(!empty($r['desired_times'])) {
                                $times = is_string($r['desired_times']) ? explode(',', $r['desired_times']) : $r['desired_times'];
                            }
                        ?>
                        <?php if(!empty($times)): ?>
                            <div style="margin-top:4px;">
                            <?php foreach($times as $t): ?>
                                <span style="display:inline-block; background:#f0f0f0; padding:2px 6px; border-radius:4px; font-size:12px; margin-right:4px;"><?= htmlspecialchars(trim($t)) ?></span>
                            <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($r['memo'] ?? '') ?></td>
                    <td>
                        <select id="status_<?= $r['id'] ?>">
                            <option value="대기 중" <?= ($r['status'] ?? '') == '대기 중' ? 'selected' : '' ?>>대기 중</option>
                            <option value="예약확정" <?= ($r['status'] ?? '') == '예약확정' ? 'selected' : '' ?>>예약확정</option>
                            <option value="시술완료" <?= ($r['status'] ?? '') == '시술완료' ? 'selected' : '' ?>>시술완료</option>
                            <option value="취소/거절" <?= ($r['status'] ?? '') == '취소/거절' ? 'selected' : '' ?>>취소/거절</option>
                        </select>
                    </td>
                    <td>
                        <button class="status-btn" onclick="updateStatus(<?= $r['id'] ?>)">저장</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($reservations)): ?>
                <tr><td colspan="8" style="text-align:center;">상담 신청 내역이 없습니다.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <script>
    function updateStatus(id) {
        const status = document.getElementById('status_' + id).value;
        fetch('/admin/api_update_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + id + '&status=' + encodeURIComponent(status)
        })
        .then(res => res.text())
        .then(data => {
            if(data === 'success') {
                alert('상태가 업데이트 되었습니다.');
            } else {
                alert('업데이트 실패: ' + data);
            }
        });
    }
    </script>
</body>
</html>


