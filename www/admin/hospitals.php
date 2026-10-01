<?php
// admin/hospitals.php
session_start();
require_once '../config/db_connect.php';

$hospitals = [];
if ($db_connected) {
    try {
        $stmt = $pdo->query("SELECT * FROM hospitals ORDER BY created_at DESC");
        $hospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {}
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>입점 병원 관리 - 여우언니 Admin</title>
    <style>
        :root { --primary: #f5576c; --dark: #333; --bg: #f5f6f8; }
        body { font-family: 'Inter', sans-serif; margin: 0; background: var(--bg); color: var(--dark); }
        .sidebar { width: 250px; height: 100vh; background: #fff; position: fixed; border-right: 1px solid #eee; }
        .logo { padding: 20px; font-size: 24px; font-weight: 900; color: var(--primary); border-bottom: 1px solid #eee; }
        .nav-link { display: block; padding: 15px 20px; color: #555; text-decoration: none; font-weight: 600; border-bottom: 1px solid #fafafa; }
        .nav-link:hover, .nav-link.active { background: #fff0f5; color: var(--primary); }
        .main-content { margin-left: 250px; padding: 30px; }
        .header { display:flex; justify-content:space-between; align-items:center; margin-bottom: 30px; }
        .title { font-size: 24px; font-weight: 800; }
        .btn-add { background: var(--primary); color: #fff; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 14px; }
        
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
        <a href="hospitals.php" class="nav-link active">🏥 입점 병원 관리</a>
        <a href="events.php" class="nav-link">🎁 시술 이벤트 관리</a>
        <a href="reservations.php" class="nav-link">💳 결제/정산 관리</a>
        <a href="reports.php" class="nav-link">🚨 유저 신고 관리</a>
        <a href="/" class="nav-link" style="margin-top:50px; color:#999;">앱으로 돌아가기</a>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="title">제휴/입점 병원 리스트</div>
            <a href="#" class="btn-add" onclick="alert('병원 신규 등록 기능은 개발 예정입니다.');">➕ 새 병원 등록</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>병원 ID</th>
                    <th>병원명</th>
                    <th>주소</th>
                    <th>연락처</th>
                    <th>등록일시</th>
                    <th>관리</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($hospitals)): ?>
                <tr><td colspan="6" style="text-align:center; padding:30px; color:#999;">등록된 병원이 없습니다. DB를 확인해주세요.</td></tr>
                <?php else: ?>
                    <?php foreach($hospitals as $h): ?>
                    <tr>
                        <td>#<?= $h['id'] ?></td>
                        <td style="font-weight:700;"><?= htmlspecialchars($h['name']) ?></td>
                        <td style="font-size:12px; color:#666;"><?= htmlspecialchars($h['address']) ?></td>
                        <td><?= htmlspecialchars($h['contact']) ?></td>
                        <td style="color:#888; font-size:12px;"><?= $h['created_at'] ?></td>
                        <td>
                            <button class="btn-action" onclick="alert('수정 기능 준비중');">수정</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
