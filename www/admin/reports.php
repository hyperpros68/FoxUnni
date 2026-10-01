<?php
// admin/reports.php
session_start();
require_once '../config/db_connect.php';

$reports = [];
if ($db_connected) {
    try {
        $stmt = $pdo->query("SELECT * FROM reports ORDER BY created_at DESC LIMIT 100");
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {}
}

function getReportStatusBadge($status) {
    switch($status) {
        case 'pending': return '<span style="background:#ffc107; color:#000; padding:4px 8px; border-radius:4px; font-size:12px;">접수됨(대기)</span>';
        case 'reviewed': return '<span style="background:#17a2b8; color:#fff; padding:4px 8px; border-radius:4px; font-size:12px;">확인완료</span>';
        case 'action_taken': return '<span style="background:#dc3545; color:#fff; padding:4px 8px; border-radius:4px; font-size:12px;">블라인드/경고</span>';
        default: return $status;
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>신고 접수 내역 - 여우언니 Admin</title>
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
        .btn-action { background:#333; color:#fff; padding:6px 12px; border:none; border-radius:4px; font-size:12px; cursor:pointer; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">🦊 여우언니 Admin</div>
        <a href="index.php" class="nav-link">📊 대시보드</a>
        <a href="reservations.php" class="nav-link">💳 결제/예약 관리</a>
        <a href="reports.php" class="nav-link active">🚨 유저 신고 관리</a>
        <a href="/" class="nav-link" style="margin-top:50px; color:#999;">앱으로 돌아가기</a>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="title">유저 컨텐츠 신고 내역 (스토어 리젝 방어)</div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>신고 ID</th>
                    <th>분류</th>
                    <th>대상 번호(Target ID)</th>
                    <th>신고자(User ID)</th>
                    <th>신고 사유</th>
                    <th>상태</th>
                    <th>접수일시</th>
                    <th>관리</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($reports)): ?>
                <tr><td colspan="8" style="text-align:center; padding:30px; color:#999;">접수된 신고 내역이 없습니다. 클린한 상태입니다! ✨</td></tr>
                <?php else: ?>
                    <?php foreach($reports as $r): ?>
                    <tr>
                        <td>#<?= $r['id'] ?></td>
                        <td><span style="background:#eee; padding:2px 6px; border-radius:4px; font-size:11px;"><?= strtoupper($r['content_type']) ?></span></td>
                        <td><?= $r['target_id'] ?></td>
                        <td><?= $r['reporter_user_id'] ?></td>
                        <td style="color:#d9534f; font-weight:600;"><?= htmlspecialchars($r['reason']) ?></td>
                        <td><?= getReportStatusBadge($r['status']) ?></td>
                        <td style="color:#888; font-size:12px;"><?= $r['created_at'] ?></td>
                        <td>
                            <?php if($r['status'] === 'pending'): ?>
                            <button class="btn-action" onclick="alert('블라인드 처리 완료 API 연동 예정');">블라인드 처리</button>
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
