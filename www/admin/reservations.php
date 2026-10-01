<?php
// admin/reservations.php
session_start();
require_once '../config/db_connect.php';

$reservations = [];
if ($db_connected) {
    try {
        $stmt = $pdo->query("SELECT r.*, u.phone FROM reservations r LEFT JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC LIMIT 100");
        $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {}
}

function getStatusBadge($status) {
    switch($status) {
        case 'pending': return '<span style="background:#ffc107; color:#000; padding:4px 8px; border-radius:4px; font-size:12px;">결제대기</span>';
        case 'wait_call': return '<span style="background:#fd7e14; color:#fff; padding:4px 8px; border-radius:4px; font-size:12px; font-weight:bold;">해피콜(전화) 대기 🔔</span>';
        case 'confirmed': return '<span style="background:#007bff; color:#fff; padding:4px 8px; border-radius:4px; font-size:12px;">예약확정</span>';
        case 'paid': return '<span style="background:#28a745; color:#fff; padding:4px 8px; border-radius:4px; font-size:12px;">결제완료</span>';
        case 'completed': return '<span style="background:#17a2b8; color:#fff; padding:4px 8px; border-radius:4px; font-size:12px;">시술완료</span>';
        case 'cancelled': return '<span style="background:#dc3545; color:#fff; padding:4px 8px; border-radius:4px; font-size:12px;">취소됨</span>';
        default: return $status;
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>예약/결제 관리 - 여우언니 Admin</title>
    <style>
        :root { --primary: #f5576c; --dark: #333; --bg: #f5f6f8; }
        body { font-family: 'Inter', sans-serif; margin: 0; background: var(--bg); color: var(--dark); }
        .sidebar { width: 250px; height: 100vh; background: #fff; position: fixed; border-right: 1px solid #eee; }
        .logo { padding: 20px; font-size: 24px; font-weight: 900; color: var(--primary); border-bottom: 1px solid #eee; }
        .nav-link { display: block; padding: 15px 20px; color: #555; text-decoration: none; font-weight: 600; border-bottom: 1px solid #fafafa; }
        .nav-link:hover, .nav-link.active { background: #fff0f5; color: var(--primary); }
        .main-content { margin-left: 250px; padding: 30px; }
        .header { margin-bottom: 30px; }
        .title { font-size: 24px; font-weight: 800; }
        
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #fdfdfd; font-weight: 700; color: #555; }
        tr:hover { background: #fafafa; }
        .btn-action { background:#007bff; color:#fff; padding:6px 12px; border:none; border-radius:4px; font-size:12px; cursor:pointer; font-weight:bold; }
    </style>
    <script>
        function confirmBooking(id, phone) {
            if(confirm(phone + " 번호로 고객과 통화(해피콜)를 마치셨습니까? 예약 확정 처리합니다.")) {
                fetch('/api/confirm_booking.php?id=' + id)
                    .then(r => r.json())
                    .then(data => {
                        if(data.success) {
                            alert("예약이 확정되었습니다!");
                            location.reload();
                        } else {
                            alert("오류 발생");
                        }
                    });
            }
        }
    </script>
</head>
<body>
    <div class="sidebar">
        <div class="logo">🦊 여우언니 Admin</div>
        <a href="index.php" class="nav-link">📊 대시보드</a>
        <a href="reservations.php" class="nav-link active">💳 결제/예약 관리</a>
        <a href="reports.php" class="nav-link">🚨 유저 신고 관리</a>
        <a href="/" class="nav-link" style="margin-top:50px; color:#999;">앱으로 돌아가기</a>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="title">최근 결제/예약 내역</div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>예약번호</th>
                    <th>병원명</th>
                    <th>시술명</th>
                    <th>예약자명</th>
                    <th>연락처</th>
                    <th>결제금액</th>
                    <th>상태</th>
                    <th>예약일시</th>
                    <th>액션(관리)</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($reservations)): ?>
                <tr><td colspan="9" style="text-align:center; padding:30px; color:#999;">예약 내역이 없습니다.</td></tr>
                <?php else: ?>
                    <?php foreach($reservations as $res): ?>
                    <tr>
                        <td>#<?= $res['id'] ?></td>
                        <td><?= htmlspecialchars($res['hospital_name']) ?></td>
                        <td><?= htmlspecialchars($res['event_title']) ?></td>
                        <td><?= htmlspecialchars($res['user_name']) ?></td>
                        <td><?= htmlspecialchars($res['phone'] ?? '-') ?></td>
                        <td style="font-weight:700;"><?= number_format($res['amount']) ?>원</td>
                        <td><?= getStatusBadge($res['status']) ?></td>
                        <td style="color:#888; font-size:12px;"><?= $res['created_at'] ?></td>
                        <td>
                            <?php if($res['status'] === 'wait_call'): ?>
                            <button class="btn-action" onclick="confirmBooking(<?= $res['id'] ?>, '<?= $res['phone'] ?>')">전화하기 & 확정</button>
                            <?php else: ?>
                            -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
